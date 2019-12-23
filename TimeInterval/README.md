TimeInterval
============

The TimeInterval class object reflects a (signed) time interval limited to around 3.000.000 years (approx. 11.000.000.000 hours) 
This class allows you to easily store time values as an integer (e.g. mysql:bigint, providing a much larger range of values than using mysql:time)  
Furthermore it handles some limitations/ bugs of PHP\DateInterval and PHP\DateTime (e.g. format conversion, construction by designator)

## Limitations
+ The length of the time period is limited to PHP_MAX_INT seconds and a strlength of 14 digits, which equals around 3 millon years on 64 bit systems.
+ Microseconds are not handled.
+ years are always interpreted as a static period of 365 days if period constructor is used and Y is set.
+ month are always interpreted as a static period of 30 days if period constructor is used and M is set.

## Class Constructor

```
   /**
     * @param $interval_spec int string
     * 1) int or numeric will be handled as seconds
     * 2) valid signed time string in format (-)(h)hh:mm:ss (limited to approx. 11.000.000.000 hours)
     * 3) valid string according to ISO-8601 like "P6Y9M5DT3H45M7S"
     * 
     * The starting letter "P" in the interval spec as required in PHP\Date Interval
     * is optional (for conformity) but not required since it is not really needed
     * the time separator "T" is required if time values are assigned
     * every designator is optional - at least one is required
     * using multiple designators the order as shown below is required
     * prepend a minus to declare the value as negative
     *
     * period designators and description
     * Y    years - interpreted always as a year of 365 days
     * M    months - interpreted always as a month of 30 days
     * W    weeks. These CAN get converted into days, so CAN be combined with D.
     * D    days
     * H    hours
     * M    minutes
     * S    seconds
     * @see PHP\DateInterval::__construct()
     *
     * if a simple signed/ unsigned numeric value is given this will be interpreted as "<sign>PT<value>S"
     * 
     */
    public function __construct($spec = null)

```

## Protected properties

*Use related methods to get the values*

+ `$stamp` daytime or time interval reflected by a signed integer value (seconds), default: null
+ `$separator` Separator used for direct output, default: ':'

## Methods

### Set, change or manipulate value

***stamp()***

```
/** 
     * setter/ getter for protected property stamp - validation of type and range
     * @param bool/ null/ int
     * @return $stamp null/ signed int stamp
     * @throws Exception if type is invalid
     *
     */
    public function stamp($stamp = true) 
```

***createFromTimeString()***
 
```
    /**
     * set stamp from a valid signed time string in format (-)(h)hh:mm:ss
     * 
     * @param valid time string with colons as separator as used for mysql time field or daytime according to IS0-8601
     * @return bool
     * @throws Exception
     *
     */
    public function createFromTimeString($string = null)
```

***invert()***

```
    /** 
     * invert the stamp
     * @return current state, 0=positive, 1=negative
     *
     */
    public function invert()
```
    
***add()***

```  
    /** 
     * add a time interval
     * @param $value int / string / TimeInterval object
     * @return $this (modified)
     *
     */
    public function add($value)
```

***sub()***

```    
    /** 
     * subtract a time interval
     * @param $value int / string / TimeInterval object
     * @return $this (modified)
     *
     */
    public function sub($value)  
```

### Output 

***out()*** called by magic ***__toString()***

```
    /** 
     * standard output according to ISO-8601 standard
     * fractions will be rounded if showSec = 0
     * @param int $showSec
     * @param int $round
     * @param bool $daytime
     * @return null/ signed string in format (-)(h)hh:mm:ss
     * 
     */
    public function out($showSec = 1, $round = 0)
```

***format()***

```
    /** 
     * use the following placeholders according to PHP date() and strftime():
     * a  %P   Lowercase Ante meridiem and Post meridiem   am or pm
     * A  %p   Uppercase Ante meridiem and Post meridiem   AM or PM
     * g  %-I  12-hour format of an hour without leading zeros     1 through 12
     * G  %-H  24-hour format of an hour without leading zeros     0 through 23
     * h  %I   12-hour format of an hour with leading zeros    01 through 12
     * H  %H   24-hour format of an hour with leading zeros    00 through 23
     * i  %M   Minutes with leading zeros  00 to 59
     * s  %S   Seconds, with leading zeros     00 through 59
     *
     * if a percent sign is present the format string will be interpretet as strftime() format
     * the percent sign is not allowed in date() format
     * escape letters and percent sign (strftime() format only) with leading backslash if needed
     * any backslash will be removed from the output
     * if hours exceed 24 g,G,h,H will return the full value, meridian placeholder will be ignored
     * currently the negative values are not supported
     * @param string $format
     * @param int $round if fractions should be returned and seconds not set. Default 0 will floor the smallest unit
     * @param bool $daytime convert to a valid daytime @see getDaytime()
     * @see out() for simple formatting
     * @throws Exception if no replacement character could be found
     * 
     */
    public function format($format, $round = 0, $daytime = false)
```

***days()***

```
    /** 
     * number of days
     * @return int unsigned number (without fractions) of days (=== 24 hrs) of this time interval
     *
     */
    public function days()
```

***separator()***

```
    /** 
     * set separator
     * @param string $separator (can be everything except numbers)
     * @return string current separator
     *
     */
    public function separator($set = '')
```

***sign()***

```
    /** 
     * @return string sign (-) if stamp is negative
     *
     */
    public function sign()
```
    
### Conversion to a valid daytime stamp (static)

***getDaytime()***

```
    /** 
     * convert any signed stamp (sec) to an unsigned stamp corresponding to a valid Daytime
     * e.g.
     *   stamp |   conversion | time
     * --------+--------------+---------
     *    9030 |         9030 | 02:30:30
     *   86400 |            0 | 00:00:00 (the day after: 24:00:00 == 00:00:00)
     *  129904 |        43504 | 12:05:04 (the day after)
     *   -3600 |        82800 | 23:00:00 (the day before)
     * -604798 |            2 | 00:00:02 (seven days before)
     *
     * @param int (signed) $stamp
     * @return int (unsigned) $output
     */
     public static function getDaytime($stamp)
```

## Usage examples

3 different ways to instantiate a TimeInterval object
  
```
$x = new TimeInterval('P5600Y6M3W2DT23H5M7S');
$y = new TimeInterval('490564895:05:07');

$z = new TimeInterval();
$z->stamp = 1766033622307;
```

Manipulation and output  

```
 echo $x; // 490564895:05:07
 echo $x->days(); // 20440203
 echo $x->add($y)->add('P8Y6WT5H')->sub(3)->out(1); // 981831603:10:11
 echo $x->days(); // 20440203
 echo $x->format('G \hour\s i M\inute\s and s \second\s'); // 981831603 hours 10 Minutes and 11 seconds
```




