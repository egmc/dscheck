dscheck
=======

dscheck is simple health checker using HTTP

## require

php5.3以上

## Installation

```
git clone git://github.com/egmc/dscheck.git
cd dscheck
curl -s https://getcomposer.org/installer | php
php composer.phar install

```

## Config

create dscheck.yaml

```yaml
silent_mode: false
result_file_path: tmp/result.tmp
check_list:
 - url: http://example1.net/
   check_string: "OK"
   name: "example1"
 - url: http://example2.net/
   check_string: "OK"
   name: "example2"
mail_to_list:
 - aaa@example.net
 - aaa2@example.net
mail_from: noftification@example3.net
```

## Run(add crontab to check)

```
php dscheck.php
```