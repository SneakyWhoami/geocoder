#!/bin/bash

DATA="NZ.txt"
ADMIN1="admin1CodesASCII.txt"
ADMIN2="admin2Codes.txt"
OUTPUT="test.sqlite"
DATAZIP="http://download.geonames.org/export/dump/NZ.zip"

if [ ! -f "$DATA" ]; then
  echo "Downloading cities from Geonames..."
  wget $DATAZIP
  unzip "NZ.zip"
else
  echo "Using existing $DATA"
fi

if [ ! -f "$ADMIN1" ]; then
  echo "Downloading admin1 from Geonames..."
  wget "http://download.geonames.org/export/dump/admin1CodesASCII.txt"
else
  echo "Using existing $ADMIN1"
fi

if [ ! -f "$ADMIN2" ]; then
  echo "Downloading admin2 from Geonames..."
  wget "http://download.geonames.org/export/dump/admin2Codes.txt"
else
  echo "Using existing $ADMIN2"
fi

# Disabled this check. I assume you want to add the new tables directly to your staging database

#if [ -f "$OUTPUT" ]; then
#  echo
#  echo "The file $OUTPUT already exists."
#  read -p "Do you want to override it? (y/N) " -n 1 -r
#  echo
#  if [[ ! $REPLY =~ ^[Yy]$ ]]; then
#    exit 1
#  fi
#
#  rm "$OUTPUT"
#fi

echo
echo "Generating..."

# only get populated places and localities with both admin1 and admin2 information
grep -e 'PPL\|LCTY' $DATA | awk 'BEGIN { FS="\t"; OFS=";" } { gsub("\"", "", $2); gsub(";", "", $2); print $1,$2,$9,$11,$12 }' | grep -v ';$' > features.tsv
# only get populated places and localities with both admin1 and admin2 information
grep -e 'PPL\|LCTY' $DATA | awk 'BEGIN { FS="\t"; OFS=";" } { print $1,$5,$6 }' | grep -v ';$' > coordinates.tsv
#next two lines, i'm ignoring all the non-NZ stuff
awk 'BEGIN { FS="\t"; OFS=";" } { split($1, id, "."); gsub("\"", "", $2); gsub(";", "", $2); print id[1],id[2],$2 }' $ADMIN1 | grep NZ > admin1.tsv
#next two lines, i'm ignoring all the non-nz stuff
awk 'BEGIN { FS="\t"; OFS=";" } { split($1, id, "."); gsub("\"", "", $2); gsub(";", "", $2); print id[1],id[2],id[3],$2 }' $ADMIN2 | grep NZ > admin2.tsv

echo '
CREATE TABLE coordinates(
  feature_id INTEGER,
  latitude REAL,
  longitude REAL,
  PRIMARY KEY (feature_id)
);

CREATE TABLE features(
  id INTEGER,
  name TEXT,
  country_id TEXT,
  admin1_id INTEGER,
  admin2_id INTEGER,
  PRIMARY KEY (id)
);

CREATE TABLE admin1(
  country_id TEXT,
  id INTEGER,
  name TEXT,
  PRIMARY KEY (country_id, id)
);

CREATE TABLE admin2(
  country_id TEXT,
  a1id TEXT,
  id INTEGER,
  name TEXT,
  PRIMARY KEY (country_id, a1id, id)
);

CREATE VIEW everything AS
  SELECT
    features.id,
    features.name,
    admin1.id AS admin1_id,
    admin1.name AS admin1_name,
    admin2.id AS admin2_id,
    admin2.name AS admin2_name,
    coordinates.latitude AS latitude,
    coordinates.longitude AS longitude
  FROM features
    LEFT JOIN admin1 ON features.admin1_id = admin1.id
    LEFT JOIN admin2 ON features.admin2_id = admin2.id
    JOIN coordinates ON features.id = coordinates.feature_id;

.separator ";"
.import coordinates.tsv coordinates
.import features.tsv features
.import admin1.tsv admin1
.import admin2.tsv admin2

CREATE INDEX coordinates_lat_lng ON coordinates (latitude, longitude);
' | sqlite3 "$OUTPUT"

COUNT=`sqlite3 "$OUTPUT" "SELECT COUNT(id) FROM features;"`
echo "Created $OUTPUT with $COUNT features."


