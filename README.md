# geocoder
A fast, hybrid offline/online, single country reverse geocoder in PHP with easy one-shot static function

## Inspiration and Use Case
I wanted to do a great number of very fast and fairly coarse reverse geolocations. I didn't want to hammer a third party server. I'm only interested in one country. So I hacked together this little kit for myself, and I'm sharing it here approximately feature-complete so that others may learn from it when they have a mental block, and improve/adapt it to their own needs. Most of the hard thinking was done by [lucaspiller in his nodejs project](https://github.com/lucaspiller/offline-geocoder) (hence I've released under the same license). You should check out his wonderful project if you want some more ideas about customizing the database as his targets a slightly different feature set.

## Requirements
Bash. Wget. PHP5 or greater with PDO_sqlite, DOM and url fopen. About 500K disk space for the places database (if using NZ).

## Installation
One shell script to set up the database. One php file to include. Read through the shell script before running it (both here and upstream) as you may find I'm not getting quite the information you want. As an example, if you want to use a country other than NZ, you will need to edit the setup script. As I've got it right now, it will attempt to add its tables to the target database. *It will not try to drop those tables first!* This is for your own protection ;-). The script will leave a few .tsv files lying around after running. They're left to help you quickly double-check your setup - they're not needed if you have successfully got your database up and running. If your queries run into the thousands, add your maps API key to the PHP file as per usual *just in case* you end up pinging the online maps provider more than you thought.

### Summary:
* check shell script
* run shell script
* verify data
* test PHP script
* ???
* Profit

## Usage
```php
// set things up
require_once 'GeoCode.php';
$db = new PDO('sqlite:test.sqlite');
// here are some example queries
// we let the db figure out how to handle the datatype: sqlite doesn't really care
var_dump(GeoCode::get(-40.877739, 175.01425, $db));
var_dump(GeoCode::get('-41.458345', '172.812571', $db));
// easy as that
// output is like array('name' => 'Rolleston', 'admin2' => Selwyn District, 'admin1' => Canterbury);
```
You may only be interested in the database and not the PHP bit. That makes sense. You might want to do subselects on insertions, or use another language for your main logic, or whatever. That's cool too.

## Operational Overview
Mostly set and forget. the `get()` method will try to use the offline geocoder first. If that fails, it will try to use an online geocoder (google by default). Methods for ONLY trying offline or online geocoding are separated from `get()` and made public, so you can provide your own logic if that tickles your fancy. *This kit will only tell you roughly whereabouts in a country something is, not its exact address! But it will do it pretty fast.* PHP because I like to spin up and spin down rapidly, and PHP was already set up.

## License
MIT.

## Future
No known issues right now, does what I want. If you have a use case similar to mine and you find it not doing its job, please feel free to submit a bug :-)
