## WooCommerce Support Helper

### Description

This is still a work in progress, so please do not use on a live site.

This plugin is for WooCommerce Support. It's aim is to allow Happiness Engineers to install it and copy settings from WooCommerce to import into their store efficiently. 


### Features

#### Export shipping settings

* Exports and imports shipping zones. When importing, it will add additional shipping zones with `(imported)` in the name.
* Exports and imports shipping methods included in the zones.
* Export includes main shipping method settings, and it will overwrite any settings in the store. Example, if FedEx is installed, it will overwrite the data in the store with the imported data like API keys, etc.
* Exports and imports shipping classes. On import, it will add to what already exists. If a classe with the same name is found, it is skipped. These have to be brought over or Flat Rate and Table Rates do not work.
* Exports and imports Table Rate settings. 



### Installation

1. Download the .zip file.
1. Go to Plugins > Add New and choose Upload at the top.
1. Upload the .zip file and activate. 


### Usage

After installing & activating the plugin:

1. Go to WooCommerce > Support Helper.