<?php

  Class extension_stream_list extends Extension{

    public function about(){
      return array(
        'name' => 'Stream List',
        'version' => '.2',
        'release-date' => '2013-06-20',
        'author' => array(
          'name' => 'Colin Brogan',
          'website' => 'http://cbrogan.com',
          'email' => 'colinbrogan@gmail.com'
        ),
        'description' => 'A little field to allow your client to upload sounds directly to a soundcloud account, without having to visit that sight independantly.'
      );
    }

    public function install(){
      try {
        Symphony::Database()->query("
          CREATE TABLE IF NOT EXISTS `sym_fields_stream_list` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `field_id` INT(11) UNSIGNED NOT NULL,
            `app_id` VARCHAR(15),
            `soundcloud_user` VARCHAR(15),
            `soundcloud_pass` VARCHAR(15),
            PRIMARY KEY (`id`),
            UNIQUE KEY `field_id` (`field_id`)
          ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ");
      }
      catch (Exception $ex) {
        $extension = $this->about();
        Administration::instance()->Page->pageAlert(__('An error occurred while installing %s. %s', array($extension['name'], $ex->getMessage())), Alert::ERROR);
        return false;
      }
      return true;
    }

    public function update() {
      return true;
    }

    public function uninstall(){
      if(parent::uninstall() == true){
        try {
          Symphony::Database()->query("DROP TABLE `sym_fields_stream_list`");
          return true;
        }
        catch (Exception $ex) {
          $extension = $this->about();
          Administration::instance()->Page->pageAlert(__('An error occurred while uninstalling %s. %s', array($extension['name'], $ex->getMessage())), Alert::ERROR);
          return false;
        }
      }

      return false;
    }

    public static function appendAssets() {
      if(class_exists('Administration')
        && Administration::instance() instanceof Administration
        && Administration::instance()->Page instanceof HTMLPage
      ) {
        Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/stream_list/assets/field_stream_list.publish.css', 'screen', 100, false);
        Administration::instance()->Page->addScriptToHead(URL . '/extensions/stream_list/assets/field_stream_list.publish.js', 100, false);
      }
    }

  }
?>