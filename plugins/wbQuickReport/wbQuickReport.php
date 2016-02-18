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

  /***************************************************************************
   *
   *
   *
   ***************************************************************************/
  function hooks() {
    return array(
      'EVENT_REPORT_BUG_FORM'        => 'EVENT_LAYOUT_BODY_END',
      'EVENT_REPORT_BUG_DATA'        => 'EVENT_REPORT_BUG_DATA',
      'EVENT_UPDATE_BUG_STATUS_FORM' => 'EVENT_UPDATE_BUG_STATUS_FORM',
      'EVENT_UPDATE_BUG'             => 'EVENT_UPDATE_BUG'
    );
  }

  /***************************************************************************
   *
   *
   *
   ***************************************************************************/
  public function EVENT_LAYOUT_BODY_END( $t_event, $t_project_id ){
    $this->html_quickVersion(array(
      'project_id' => $t_project_id,
      'field'      => 'target_version'
      ));
  }

  /***************************************************************************
   *
   *
   *
   ***************************************************************************/
  public function EVENT_REPORT_BUG_DATA( $t_event, $t_bug_data ){
    $t_bug_data = $this->save_quickVersion(array(
      'bug_data' => $t_bug_data,
      'field'    => 'target_version'
      ));
    return $t_bug_data;
  }

  /***************************************************************************
   *
   *
   *
   ***************************************************************************/
  public function EVENT_UPDATE_BUG_STATUS_FORM( $t_event, $t_bug_id ){
    $t_bug = bug_get( $t_bug_id );
    if(
      version_should_show_product_version( $t_bug->project_id )
      && !bug_is_readonly($t_bug_id )
      && access_has_bug_level( config_get( 'update_bug_threshold' ), $t_bug_id )
      ) {
      $this->html_quickVersion(array(
        'project_id' => $t_bug->project_id,
        'bug_id'     => $t_bug_id,
        'field'      => 'fixed_in_version'
        ));
    }
  }

  /***************************************************************************
   *
   *
   *
   ***************************************************************************/
  public function EVENT_UPDATE_BUG( $t_event, $t_bug_data, $t_bug_id ){
    $t_bug_data = $this->save_quickVersion(array(
      'bug_data' => $t_bug_data,
      'field'    => 'fixed_in_version'
      ));
    return $t_bug_data;
  }

  /***************************************************************************
   *
   *
   *
   ***************************************************************************/
  public function save_quickVersion( $params ){

    $params     = is_array($params) ? $params : array('project_id' => $params);
    $t_field    = $params['field'];
    $t_bug_data = &$params['bug_data'];

    if( access_has_project_level( config_get( 'manage_project_threshold' ), $t_bug_data->project_id, $t_user ) ){
      $f_create_version = trim( gpc_get_string( 'create_'.$t_field, '' ) );
      $f_select_obsolete_version = trim( gpc_get_string( 'select_obsolete_'.$t_field, '' ) );
      if( !is_blank( $f_create_version ) ){
        $t_project_id = $t_bug_data->project_id;
        $f_create_version_released = gpc_get_bool( 'create_'.$t_field.'_released' );
        $f_create_version_obsolete = gpc_get_bool( 'create_'.$t_field.'_obsolete' );
        if( version_is_unique( $f_create_version, $t_project_id ) ){
          $t_version_id = version_add( $t_project_id, $f_create_version );
          event_signal( 'EVENT_MANAGE_VERSION_CREATE', array( $t_version_id ) );
          if( $f_create_version_released || $f_create_version_obsolete ){
            $t_version = version_get( $t_version_id );
            if( $f_create_version_released )
              $t_version->released = $f_create_version_released ? VERSION_RELEASED : VERSION_FUTURE;
            if( $f_create_version_obsolete )
              $t_version->obsolete = $f_create_version_obsolete ? VERSION_RELEASED : VERSION_FUTURE;
            version_update( $t_version );
            event_signal( 'EVENT_MANAGE_VERSION_UPDATE', array( $t_version->id ) );
          }
        }
        $t_apply_version = $f_create_version;
      }
      elseif( !is_blank($f_select_obsolete_version) ) {
        $t_apply_version = $f_select_obsolete_version;
      }
      if( !empty($t_apply_version) ){
        $t_bug_data->{ $t_field } = $t_apply_version;
        if( $t_field == 'target_version' && empty($t_bug_data->product_version) ){
          $t_bug_data->version = $t_apply_version;
        }
        if( $t_field != 'fixed_in_version' && $t_bug_data->status >= config_get( 'bug_readonly_status_threshold' ) ){
          $t_bug_data->fixed_in_version = $t_bug_data->target_version;
        }
      }
    }

    return $t_bug_data;

  }

  /***************************************************************************
   *
   *
   *
   ***************************************************************************/
  public function html_quickVersion( $params ){

    $params       = is_array($params) ? $params : array('project_id' => $params);
    $t_field      = $params['field'];
    $t_project_id = $params['project_id'];
    $t_versions   = version_get_all_rows( $t_project_id, null, true );

    if( access_has_project_level( config_get( 'manage_project_threshold' ), $t_project_id, auth_get_current_user_id() ) ){
      ?>
      <!-- wbQuickReport -->
      <tr style="display:none;" id="create_<?php echo $t_field; ?>" <?php echo helper_alternate_class() ?>>
        <td class="category">
          <label for="create_<?php echo $t_field; ?>">
            <?php echo lang_get( 'plugin_wbquickreport_create_' . $t_field ); ?>
          </label>
        </td>
        <td>
          <input type="text" name="create_<?php echo $t_field; ?>" maxlength="10" />
          <label>
            <?php echo lang_get( 'released' ); ?>
            <input type="checkbox" name="create_<?php echo $t_field; ?>_released" />
          </label>
          <label>
            <?php echo lang_get( 'obsolete' ); ?>
            <input type="checkbox" name="create_<?php echo $t_field; ?>_obsolete" />
          </label>
        </td>
      </tr>
      <tr style="display:none;" id="select_obsolete_<?php echo $t_field; ?>" <?php echo helper_alternate_class() ?>>
        <td class="category">
          <label for="create_<?php echo $t_field; ?>">
            <?php echo lang_get( 'plugin_wbquickreport_select_obsolete_' . $t_field ); ?>
          </label>
        </td>
        <td>
          <select name="select_obsolete_<?php echo $t_field; ?>"><?php
            echo '<option></option>';
            $t_max_length = config_get( 'max_dropdown_length' );
            foreach( $t_versions AS $t_version ){
              if( (int)$t_version['obsolete'] == 1 ){
                echo '<option value="' . string_attribute($t_version['version']) . '">';
                echo string_shorten( string_attribute(prepare_version_string($t_project_id, $t_version['id'])), $t_max_length );
                echo '</option>';
              }
            }
          ?></select>
        </td>
      </tr>
      <script>
        var createQuickVersionToggle = function(){
          document.querySelector('select[name=<?php echo $t_field; ?>]').onchange = function(){
            document.getElementById('create_<?php echo $t_field; ?>').style.display = (this.value == '' ? 'table-row' : 'none');
            document.getElementById('select_obsolete_<?php echo $t_field; ?>').style.display = (this.value == '' ? 'table-row' : 'none');
          }
          document.getElementById('create_<?php echo $t_field; ?>').style.display = (document.querySelector('select[name=<?php echo $t_field; ?>]').value == '' ? 'table-row' : 'none');
          document.getElementById('select_obsolete_<?php echo $t_field; ?>').style.display = (document.querySelector('select[name=<?php echo $t_field; ?>]').value == '' ? 'table-row' : 'none');
        }
        (window.addEventListener
          ? window.addEventListener('load', createQuickVersionToggle, false)
          : (window.attachEvent
            ? window.attachEvent('onload', createQuickVersionToggle)
            : createQuickVersionToggle()
            )
          );
      </script>
      <?php
    }

  }

}
