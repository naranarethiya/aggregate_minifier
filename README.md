# Aggregate Minifier
Minify and aggregate css and javascript files on production mode using php, This library works in same way [Drupal](https://www.drupal.org/) css and javascript aggregation functionality works.

## Getting started
Let me first give you intro of the project, what is the project is.

If you ever worked [Drupal](https://www.drupal.org/) or [Magento](https://magento.com/), you had notice that they automatically compress and aggregate all individual css and javascript file in one single css and javascript file while project in production mode, the same way this repo is works.

### Here is the workflow of the repository, how this repository works

1. if ENVIRONMENT constant is **not set to production**, return individual `<link>` and `<script>` tag for each element in array
   
   Example :
	```
	generate_style_link(array(
		'./css/boostrap.css','./css/custom.css','./css/home.css'
	));
	```

	Will proceed following

	```
	<link href="./css/boostrap.css"  media="all" rel="stylesheet" />
	<link href="./css/custom.css"  media="all" rel="stylesheet" />
	<link href="./css/home.css"  media="all" rel="stylesheet" />
	```

2. if ENVIRONMENT constant is **set to production**, create aggregate file in temp directory and return single file path of aggregated file. 
	
	Example :
	```
	generate_style_link(array(
		'./css/boostrap.css','./css/custom.css','./css/home.css'
	));
	```

	Will proceed following

	```
	<link href="./tmp/adbe8043a4a64b341ac76463365337a4fef1163e77c48e1900cd89e04b102290.css"  media="all" rel="stylesheet" />
	```

## Configuration

Before you start using this repository you need to set some configuration in Minifier.php

1. **ENVIRONMENT constant**
   Set it to production or development according to your project status, check above workflow section for behavior of repository according to ENVIRONMENT value.

2. **BASE_URL - Constant**
   Set this to according to your project base URL Like http://localhost/aggregate_minifier/

   Trailing slash is required

   BASE_URL constant is used to replace relative path in css files to absolute path
   
   Example.

       .header-bg {
           background-image: url(./images/header-bg.jpg);
       }
       

    will Proceed following while in production mode
       
       .header-bg {
           background-image: url(http://localhost/aggregate_minifier/images/header-bg.jpg);
       }

3. **TMP_DIR - Constant**

   Temporary directory path, it should be relative path from BASE_URL. this directory will Use to place combined css and javascript files into directory.
   
   This directory must be writable 


## Installation & usage
   This repository contain only two helper function, you can use this functions by just including Minifier.php in required file.

   1. **generate_style_link(array())**

      Use this function to add css `<link>` tag in to html code.
   	  
   	  Example of usage

	<?php
		required("Minifier.php");
		generate_style_link(array(
			'./css/boostrap.css','./css/custom.css','./css/home.css'
		));
	?>

   2. **generate_script_link(array())**

      Use this function to add javascript `<script>` tag in to html code.
   	  
   	  Example of usage

	<?php
		required("Minifier.php");
		generate_script_link(array(
			'./js/boostrap.js','./js/custom_script.js','./js/Moment.js'
		));
	?>	
