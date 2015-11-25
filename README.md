# contentful-clone-script
A script to clone source space to destination space in contentful
Script for cloning space and other stuff on contentful 

##Usage##
all you need would be to instantiate the object as 
```php
$contentful = new ContentfulStuff();
```

All you need for versitle manupulation of this script is 
-- source id
-- destination id
-- source access token
-- destionation access token
-- destionation auth token - you will have to go through creating this on contentful itself

then
```php
$contentful->cloneContentType($read_space_id, $read_access_token, $write_space_id, $write_auth_token);
//followed by 
$contentful->cloneEntity($read_space_id, $read_access_token, $write_space_id, $write_auth_token, $write_access_token)
```
