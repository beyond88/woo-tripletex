View plugin [readme](./readme.txt)  
***

## Installation

You can install the packages via composer:

```bash
composer install
```

### Used packages:
- Guzzle: https://docs.guzzlephp.org/en/stable/
- Faker: https://github.com/fzaninotto/Faker

### Manual test:  
```shell
cp test.stub test.php
```
 And run    
```injectablephp
php test.php
```

## TODO
- add metadata to customer, products and order so that we can reduce the tripletex API call while validating the existence in existing data