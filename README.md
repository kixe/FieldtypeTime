Fieldtype Time & Inputfield Time
================================

This fieldtype handles daytimes (within 24 hours) and signed time intervals. The value is returned in form of a **TimeInterval** object providing some handy output and manipulation options.

## Features
+ Big Range: approx. 28 billion hours (more than 3.000.000 year)
+ For detailed output format options and value manipulation see */TimeInterval/README.md*
+ Input autocorrection included 

## Specs
This Fieldtype stores a time value as integer and provides a TimeInterval data object as output value. The underlying MySQL type (bigint) allows a range from approx '-27.777.777.777:00:00' to '27.777.777.777:00:00' (more than 3.000.000 year)

## Input autocorrection

| Input | Value (interval) | Value (daytime) |
|------:|-----------------:|----------------:|
|   1.9 |         01:09:00 |        01:09:00 |
| 7,8%5 |         07:08:05 |        07:08:05 |
|    25 |         25:00:00 |        01:00:00 |
|    -4 |        -04:00:00 |        20:00:00 |