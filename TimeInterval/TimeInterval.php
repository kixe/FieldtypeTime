<?php

/**
 * The TimeInterval class object reflects a (signed) time interval limited to around 3.000.000 years
 * This class allows you to easily store time values as an integer, providing a much larger range of values than using mysql:time
 * This class handles some limitations of PHP\DateInterval and PHP\DateTime (e.g. format conversion, construction by designator, bugs)
 * @see PHP\DateInterval::createFromDateString() https://bugs.php.net/bug.php?id=52480
 *
 * Limitations of this class:
 * + length of the time period is limited to PHP_MAX_INT seconds and a strlength of 14 digits
 * + microseconds are not handled
 * + years are always interpreted as a static period of 365 days if period constructor is used and Y is set
 * + month are always interpreted as a static period of 30 days if period constructor is used and M is set
 *
 * @author Christoph Thelen aka kixe
 * @license Licensed under GNU/GPL v3
 * 
 * @version 1.0.5
 *
 * @since 1.0.0 init - 2018-02-21
 * @since 1.0.1 added methods add() and sub() fixed parser bug - 2018-05-19
 * @since 1.0.2 protect property stamp, added method stamp() - 2018-09-20
 * @since 1.0.3 added function className() maybe called by ProcessWire determining this as a field value object - 2020-09-16
 * @since 1.0.4 format() and out() return always strings, empty string if $stamp === null
 * @since 1.0.5 fixed bug if stamp is null functions add() sub() having no effect, days(), revert(), sign() return bool false
 * 
 */

class TimeInterval {

    protected $stamp; // time or time interval reflected by a signed integer value, default null

    /**
     * @see separator(), out()
     */
    protected $separator = ':';

    /**
     * @param $spec int string
     * 1) int or numeric will be handled as seconds
     * 2) valid signed time string in format (-)(h)hh:mm:ss (limited to approx. 11,000,000,000 hours)
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
    public function __construct($spec = null) {
        if (!is_null($spec) && !is_numeric($spec) && !is_string($spec)) throw new Exception("Failed to construct TimeInterval. Argument 1 passed to __construct() unknown or bad type.");
        if ($spec === null) $this->stamp = null;
        else if (is_int($spec)) $this->stamp = $spec;
        else if (ctype_digit(ltrim($spec, '-'))) $this->stamp = (int) $spec;
        else if (strpos($spec,'P') !== false) $this->stamp = $this->parse($spec);
        else if (strpos($spec,':') !== false) $this->createFromTimeString($spec);
        else throw new Exception("Unknown or bad format ($spec)");
    } 

    /** 
     * setter/ getter for property stamp - validation of type and range
     * @param bool/ null/ int
     * @return $stamp null/ signed int stamp
     * @throws Exception if type is invalid
     *
     */
    public function stamp($stamp = true) {
        // get
        if ($stamp === true) return $this->stamp;
        // unset 
        if ($stamp === null) {
            $this->stamp = null;
            return null;
        }
        // set
        $type = gettype($stamp);
        if ($type !== 'integer') throw new Exception("Argument 1 passed to stamp() must be of type null or integer, $type given");
         // PHP_INT_MAX or length (14 digits) exceeded
        if (strlen($stamp) > 14 || false === is_int($stamp/1)) throw new Exception("Integer overflow. Unable to set stamp with precision. ($stamp)");
        $this->stamp = $stamp;
        return $stamp;
    }

    /** 
     * add a time interval
     * @param $value int / string / TimeInterval object
     * @return $this (modified)
     *
     */
    public function add($value) {
        if ($this->stamp === null) return $this;
        $tio = $value instanceof self? $value : new TimeInterval($value);
        $this->stamp($this->stamp + $tio->stamp);
        return $this;
    }

    /** 
     * subtract a time interval
     * @param $value int / string / TimeInterval object
     * @return $this (modified)
     *
     */
    public function sub($value) {
        if ($this->stamp === null) return $this;
        $tio = $value instanceof self? $value : new TimeInterval($value);
        $this->stamp($tio->stamp *= -1);
        return $this;
    }

    /**
    public function modify($modify) {
        // DateTime::modify
        $dto = DateTime::createFromFormat('U', $this->stamp);
        $dto->modify($modify);
        $this->stamp($dto->format('U'));
        return $this;
    }
    */

    /** 
     * number of days
     * @return int unsigned number (without fractions) of days (=== 24 hrs) of this time interval 
     * @see PHP\DateInterval::days()
     *
     */
    public function days() {
        if ($this->stamp === null) return false;
        return (int) floor(abs($this->stamp / 86400));
    }

    /** 
     * invert the timestamp
     * @return bool false|int current state, 0=positive, 1=negative
     *
     */
    public function invert() {
        if ($this->stamp === null) return false;
        $this->stamp *= -1;
        return $this->stamp < 0? 1 : 0;
    }

    /** 
     * @return bool false|string sign (-) if stamp is negative
     *
     */
    public function sign() {
        if ($this->stamp === null) return false;
        return $this->stamp < 0? '-' : '';
    }

    /** 
     * set separator
     * @param string $separator (can be everything except numbers)
     * @return string current separator
     *
     */
    public function separator($set = '') {
        if ($type = gettype($set) !== 'string') throw new Exception("Argument 1 passed to separator() must be of type string, $type given");
        if (preg_match("/\d+/", $set)) return false;
        return $this->separator = $set;
    }

    /** 
     * create an array of values for each requested unit(hours, minutes, seconds) based on absolute (unsigned) stamp
     * @param string $handle define which units to create
     * @param int $round if fractions should be returned for the smallest unit (h, m). Default 0 will floor the smallest unit
     * @param int $stamp default $this->stamp
     * @return assoc array
     * 
     */
    protected function split($handle = '111', $round = 0) {
        if ($this->stamp === null) return null;

        // split stamp to get hours, minutes and seconds
        $showH = 0 === strpos($handle, '1')? true : false;
        $showM = 1 === strpos($handle, '1', 1)? true : false;
        $showS = 2 === strpos($handle, '1', 2)? true : false;
        $return = array();
        $h = $m = 0;
        $s = abs($this->stamp);
        if ($s >= 3600 && $showH) {
            $h = $s/3600;
            $_s = $s;
            $s = $s%3600;
            if (!$round || $showM+$showS) $h = intval(floor($h));
            else $h = round($h, $round);
        }
        if ($s >= 60 && $showM) {
            $m = $s/60;
            $_s = $s;
            $s = $s%60;
            if (!$round || $showS) $m = intval(floor($m));
            else $m = round($m, $round);
        }
        if ($showH) $return['h'] = $h;
        if ($showM) $return['m'] = $m;
        if ($showS) $return['s'] = $s;
        return $return;
    }

    /** 
     * convert any signed stamp (sec) to an unsigned stamp corresponding to a valid Daytime
     * e.g.
     *   stamp |       output | time
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
     public static function getDaytime($stamp) {
        $signed = $stamp < 0? true : false;
        $abs = abs($stamp);
        if ($abs == 86400) return 0;
        if ($abs > 86400) $abs = $abs%86400;
        return $signed? 86400 - $abs : $abs;
     }

    /** 
     * standard output according to ISO-8601 standard
     * fractions will be rounded if showSec = 0
     * @param int $showSec
     * @param int $round
     * @param bool $daytime
     * @return null/ signed string in format (-)(h)hh:mm:ss
     * 
     */
    public function out($showSec = 1, $round = 0) {
        if ($this->stamp === null) return '';
        $vals = $this->split("11" . (int) $showSec, (int) $round);
        foreach ($vals as $key => &$val) {
            if ($key == 'h' && strlen($val) >= 2) continue;
            if (isset($val)) $val = substr('00'.$val, -2, 2);
        }
        return $this->sign().implode($this->separator, $vals);
    }

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
    public function format($format, $round = 0, $daytime = false) {
        $type = gettype($format);
        if ($type !== 'string') throw new Exception("Argument 1 passed to format() must be of type string, $type given");
        if ($this->stamp === null) return '';
        $stamp = $this->stamp;
        $sign = $this->sign();
        $meridian = false;
        if ($daytime) {
            $stamp = self::getDaytime($this->stamp);
            $sign = '';
            if ($stamp < 43200) $meridian = true;
        } 

        // get replacement strings array
        $strftime = false;
        $regex = ($strftime = false !== strpos($format, '%'))?  "/((?<!\\\\)%-?[IH]|%[PpMS])/" : "/((?<!\\\\)[aAgGhHis])/";
        $out = preg_match_all($regex, $format, $matches);
        $rchars = array_unique(array_pop($matches));

        if ($strftime) {
            // time translation array strftime() to date()
            $conv = array('%P' => 'a', '%p' => 'A', '%-I' => 'g', '%-H' => 'G', '%I' => 'h', '%H' => 'H', '%M' => 'i', '%S' => 's');
            $rchars = array_flip($rchars);
            $rchars = array_intersect_key($conv, $rchars);
        }

        if (!$out || $rchars === false) throw new Exception("Unknown or bad format string ($format).");
        $hasS = in_array('s', $rchars)? 1 : 0;
        $hasM = in_array('i', $rchars)? 1 : 0;
        $hasH = empty(array_intersect($rchars, array('g','G','h','H')))? 0 : 1;
        $hasA = empty(array_intersect($rchars, array('a','A')))? 0 : 1;

        // get values for time units
        $parts = $this->split("$hasH$hasM$hasS", $round);

        // set values array (time)
        $rvals = array();
        if (isset($parts['h'])) {
            $val24 = ($daytime && $parts['h'] == 24)? 0 : $parts['h'];
            $val12 = ($daytime && $parts['h'] >= 13)? $parts['h'] - 12 : $parts['h'];
            $rvals['G'] = $sign . $val24;
            $rvals['g'] = $sign . $val12;
            $rvals['H'] = $sign . substr("0".$val24, -2, 2);
            $rvals['h'] = $sign . substr("0".$val12, -2, 2);
        }
        if (isset($parts['m'])) {
            $rvals['i'] = isset($parts['h'])? '' : $sign;
            $rvals['i'] .= strlen($parts['m']) < 3? substr("0".$parts['m'], -2, 2) : $parts['m'];
        }
        if (isset($parts['s'])) {
            $rvals['s'] = (isset($parts['h']) || isset($parts['m']))? '' : $sign;
            $rvals['s'] .= strlen($parts['s']) < 3? substr("0".$parts['s'], -2, 2) : $parts['s'];
        }

        // set values array (meridian)
        if ($hasA) {
            if ($meridian) $rvals['a'] = 'am';
            else if ($daytime) $rvals['a'] = 'pm';
            else $rvals['a'] = 'a';
            $rvals['A'] = strtoupper($rvals['a']);
        }

        // replace
        $rchars = array_flip($rchars);
        array_walk($rchars, function (&$item, $key, $strftime) {$item = "/((?<!\\\\)" . ($strftime? $item : $key) . ")+/";}, $strftime);
        $rvals = array_intersect_key($rvals, $rchars);
        ksort($rvals);
        ksort($rchars);
        $rchars[] ='@\\\\+@';
        $rvals[] = '';

        return preg_replace($rchars, $rvals, $format);
    }

    /**
     * set stamp from a valid signed time string in format (-)(h)hh:mm:ss
     * 
     * @param valid time string with colons as separator as used for mysql time field or daytime according to IS0-8601
     * @return bool
     * @throws Exception
     *
     */
    public function createFromTimeString($string = null) {
        if ($string === null) return false;
        $invert = false;
        if (0 === strpos($string, '-')) {
            $invert = true;
            $string = substr($string, 1);
        }
        $parts = explode(':', $string);
        if (empty($parts)) {
            $this->stamp = null;
            return false;
        }
        $parts = array_reverse(array_pad($parts,3,'0'));
        $stamp = 0;
        $m = 1;
        foreach ($parts as $part) {
            if (ctype_digit($part) == false) throw new Exception("Unknown or bad format ($string)");
            $stamp += (int) $part * $m;
            if (strlen($stamp) > 14 || false === is_int($stamp/1)) throw new Exception("Integer overflow. Unable to handle value with precision. ($string)");
            $m *= 60;
        }
        $stamp = $invert? $stamp * -1 : $stamp;
        $this->stamp($stamp);
        return true;
    }

    /**
     * parses a valid string according to ISO-8601 to specify a time duration
     * @param string $spec
     * @return int $stamp (time period in seconds)
     * @throws Exception
     *
     */
     protected function parse($spec) {
        $type = gettype($spec);
        if ($type !== 'string') throw new Exception("Argument 1 passed to parse() must be of type string, $type given");
        $regex = "@(-)?P(\d+?Y)?(\d+?M)?(\d+?W)?(\d+?D)?(T(\d+?H)?(\d+?M)?(\d+?S)?)?@i"; // doesn't handle fractions

        if (!preg_match_all($regex, $spec, $matches) || strlen($matches[0][0]) != strlen($spec)) throw new Exception("Unknown or bad format ($spec)");

        $stamp = 0;
        $stamp += substr($matches[2][0], 0, -1) * 315360000; // get year - a year is interpreted as a year with 365 days
        $stamp += substr($matches[3][0], 0, -1) * 2592000; // get month - a month is interpreted as a month with 30 days
        $stamp += substr($matches[4][0], 0, -1) * 604800; // get weeks - each of 7 days
        $stamp += substr($matches[5][0], 0, -1) * 86400; // get days - each of 24 hours
        $stamp += substr($matches[7][0], 0, -1) * 3600; // get hours - each of 60 minutes
        $stamp += substr($matches[8][0], 0, -1) * 60; // get minutes - each of 60 seconds
        $stamp += substr($matches[9][0], 0, -1); // get seconds - smallest unit

        // PHP_INT_MAX or length (14 digits) exceeded
        if (strlen($stamp) > 14 || false === is_int($stamp/1)) throw new Exception("Integer overflow. Unable to set stamp with precision. ($stamp)");

        return empty($matches[1][0])? $stamp : $stamp * -1;
    }

    /**
     * magic method when class object is treated like a string
     * @return string
     *
     */
    public function __toString() {
        return strval($this->out());
    }

    /**
     * render the class name
     * @return string
     *
     */
     public function className() {
        return get_class($this);
    }
}
