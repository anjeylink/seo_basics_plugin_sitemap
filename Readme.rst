Extension Manual
=================

This extensions uses a hook of the seo_basics XML sitemap to include generic urls from any plugin which has a single view. It works well with realurl.

Requirements
-----------------
TYPO3 >= 7.6.0 and <= 8.9.99 

Installation
-----------------

Just install the extension from ter by searching for "seo_basics_plugin_sitemap" in the extension manager.

Configuration
-----------------

Simply add a plugin definition to the sitemap in your TypoScript Template.
This is an example for all records of the extension tx_news:

```

|plugin.tx_seobasicspluginsitemap {  
|  extensions {  
|	
|    # configuration label (or extension name if extName is not set)  
|    news {  
|      # extension name (used for check if extension is loaded)  
|      extName = news  
|      # Insert the uid of the page which displays the single view of your plugin.  
|      detailPid = 54  
|      # The uid of your storage folder (optional)  
|      where = pid=100  
|		      
|      # The look up table  
|      table = tx_news_domain_model_news  
|		      
|      # An array of params for link building  
|      additionalParams {  
|        1 = tx_news_pi1[news]=$uid  
|      }  
|		      
|      # Mapping of fields, which adds the possibility to use alternate fields for item generation.  
|      fields {  
|        # field that used as record uid  
|        uid = uid  
|        # field used for <lastmod> in sitemap  
|        tstamp = crdate  
|        # localization parent field - default value 'l10n_parent' will be used if not set  
|        l10n_parent = l10n_parent  
|      }  
|      
|      # languages for 'alternate' tags  
|      languages {  
|        0 = en  
|        1 = sv  
|      }  
|    }  
| }  
|}  

```
