Notes
=====

## PHP\DateInterval
```
/* Properties */
public integer $y ; (fixed 365) output?
public integer $m ; (fixed 30) 
public integer $d ;
public integer $h ;
public integer $i ;
public integer $s ;
public float $f ;
public integer $invert ;
public mixed $days ;

/* Methods */
public __construct ( string $interval_spec )
public static DateInterval createFromDateString ( string $time )
public string format ( string $format )
}
```


## PHP\DateTime

```
/* Constants */
const string ATOM = "Y-m-d\TH:i:sP" ;
const string COOKIE = "l, d-M-Y H:i:s T" ;
const string ISO8601 = "Y-m-d\TH:i:sO" ;
const string RFC822 = "D, d M y H:i:s O" ;
const string RFC850 = "l, d-M-y H:i:s T" ;
const string RFC1036 = "D, d M y H:i:s O" ;
const string RFC1123 = "D, d M Y H:i:s O" ;
const string RFC2822 = "D, d M Y H:i:s O" ;
const string RFC3339 = "Y-m-d\TH:i:sP" ;
const string RFC3339_EXTENDED = "Y-m-d\TH:i:s.vP" ;
const string RSS = "D, d M Y H:i:s O" ;
const string W3C = "Y-m-d\TH:i:sP" ;
```


+ [https://secure.php.net/manual/en/datetime.formats.time.php](https://secure.php.net/manual/en/datetime.formats.time.php)
+ [https://secure.php.net/manual/en/datetime.formats.relative.php](https://secure.php.net/manual/en/datetime.formats.relative.php)

### relative strings

* \+ 1 day
* \+ one day
* \- one day
* \+ one week
* \- one week

## ISO 8601

**based on 24hours**  
[https://en.wikipedia.org/wiki/ISO_8601#Times](https://en.wikipedia.org/wiki/ISO_8601#Times)  
A time might appear as either "134730" in the basic format or "13:47:30" in the extended format.

## ISO 8601:2004

use comma OR dot to show fractions of the smallest unit
13 hours 47 minutes and a half should be displayed as "1347.5", "13:47.5", "1347,5", "13:47,5"

00:00" or "24:00 is the same one indicates the beginning of a day the other one the end.

## Mysql Time

MySQL retrieves and displays TIME values in 'HH:MM:SS' format (or 'HHH:MM:SS' format for large hours values). TIME values may range from '-838:59:59' to '838:59:59'.
values is '-838:59:59.000000' to '838:59:59.000000'.

