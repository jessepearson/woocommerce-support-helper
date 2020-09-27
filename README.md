## WooCommerce Support Helper

### Description

This is still a work in progress, so please take caution when using.

This plugin is for WooCommerce Support. It's aim is to allow Happiness Engineers to install it and copy settings from WooCommerce to import into their store or to share in GitHub reports efficiently. 


### Features

#### Export and import shipping settings

* Exports and imports shipping zones. When importing, it will add additional shipping zones with `(imported)` in the name.
* Exports and imports shipping methods included in the zones.
* Export includes main shipping method settings, and it will overwrite any settings in the store. Example, if FedEx is installed, it will overwrite the data in the store with the imported data like API keys, etc.
* Exports and imports shipping classes. On import, it will add to what already exists. If a class with the same name is found, it is skipped. These have to be brought over or Flat Rate and Table Rates do not work.
* Exports and imports Table Rate settings and table rates themselves. 

#### Export and import payment gateway settings

* Will export settings for payment gateways in use.
* When importing it overwrites any existing settings.

#### Export and import settings tabs

Export and import (overwrite) settings for:

* General settings tab.
* Product settings tab.
* Tax settings tab - tax rates not included due to they have their own csv export and import.
* Accounts & Privacy settings tab.


### Installation

1. Download the .zip file.
1. Go to Plugins > Add New and choose Upload at the top.
1. Upload the .zip file and activate. 


### Usage

After installing & activating the plugin:

1. Go to WooCommerce > Support Helper.
1. Use the tool you'd like.