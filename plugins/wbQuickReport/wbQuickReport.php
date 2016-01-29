<?php

/************************************************************************************************************************************
 *
 * wbQuickReport plugin for MantisBT
 * 2015 - David Hunt, Webuddha.com
 *
 ************************************************************************************************************************************/

require_once( config_get( 'class_path' ) . 'MantisPlugin.class.php' );

/******************************************************************************************
 *
 * Quick Bug Reporting Tools
 *
 ******************************************************************************************/
class wbQuickReportPlugin extends MantisPlugin  {

  /***************************************************************************
   *
   *
   *
   ***************************************************************************/
  function register( ) {

    // Plugin
      $this->name = lang_get( 'plugin_wbquickreport_title' );
      $this->description = lang_get( 'plugin_wbquickreport_description' );
      $this->page = 'config';
      $this->version = '0.1.0';
      $this->requires = array(
        'MantisCore' => '1.2.19',
      );
      $this->author   = 'David Hunt, Webuddha.com';
      $this->contact  = 'mantisbt-dev@webuddha.com';
      $this->url      = 'http://www.webuddha.com';

  }

}
