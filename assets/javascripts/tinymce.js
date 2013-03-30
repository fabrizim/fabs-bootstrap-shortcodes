
(function() {
  
  function register_plugin(name, title, image, content) {
    tinymce.create('tinymce.plugins.'+name, {
      init : function(ed, url) {
        ed.addButton(name, {
          title : title,
          image : url+image,
          onclick : function() {
            c = ed.selection.getContent();
            if ( typeof content == 'function') content = content( c );
            ed.selection.setContent(content);
          }
        });
      },
      createControl : function(n, cm) {
        return null;
      }
    });
    tinymce.PluginManager.add(name, tinymce.plugins[name]);
  }
  
  /********************************************************
  * Button
  *********************************************************/
  register_plugin(
    'button'
  , 'Add a Bootstrap Button'
  , '/../images/ui-button-default.png'
  , function(c){
      return '[button size="default" type="primary"]'+(c||'...Button Text...')+'[/button]';
    }
  );
  
  /********************************************************
  * Halves
  *********************************************************/
  register_plugin(
    'halves'
  , 'Add a row with 2 equal columns'
  , '/../images/layout-2-equal.png'
  , [
     '[row]',
        '[col span="6"]',
          'Column 1',
        '[/col]',
        '[col span="6"]',
          'Column 2',
        '[/col]',
      '[/row]'
    ].join('<br />')
  );
  
  /********************************************************
  * Thirds
  *********************************************************/
  register_plugin(
    'thirds'
  , 'Add a row with 3 equal columns'
  , '/../images/layout-3.png'
  , [
     '[row]',
        '[col span="4"]',
          'Column 1',
        '[/col]',
        '[col span="4"]',
          'Column 2',
        '[/col]',
        '[col span="4"]',
          'Column 3',
        '[/col]',
      '[/row]'
    ].join('<br />')
  );
  
  /********************************************************
  * Tabs
  *********************************************************/
  register_plugin(
    'tabs'
  , 'Add a tab group'
  , '/../images/ui-tab-content.png'
  , [
      '[nav type="tabs"]',
        '[item title="Item 1"]',
          'Content for Item 1',
        '[/item]',
        '[item title="Item 2"]',
          'Content for Item 2',
        '[/item]',
        '[item title="Item 3"]',
          'Content for Item 3',
        '[/item]',
      '[/nav]'
    ].join('<br />')
  );
  
  /********************************************************
  * Vertical Tabs
  *********************************************************/
  register_plugin(
    'tabs_vertical'
  , 'Add a tab group'
  , '/../images/ui-tab-content-vertical.png'
  , [
      '[nav type="tabs" tabs="left"]',
        '[item title="Item 1"]',
          'Content for Item 1',
        '[/item]',
        '[item title="Item 2"]',
          'Content for Item 2',
        '[/item]',
        '[item title="Item 3"]',
          'Content for Item 3',
        '[/item]',
      '[/nav]'
    ].join('<br />')
  );
  
  /********************************************************
  * Alerts
  *********************************************************/
  register_plugin(
    'alert'
  , 'Add an alert box'
  , '/../images/exclamation.png'
  , function(c){ return [
      '[alert]',
        (c || 'Alert Message'),
      '[/alert]'
    ].join('<br />')}
  );
  
  register_plugin(
    'alert_error'
  , 'Add an error box'
  , '/../images/exclamation-red.png'
  , function(c){ return [
      '[alert type="error"]',
        (c || 'Alert Message'),
      '[/alert]'
    ].join('<br />')}
  );
  
  register_plugin(
    'alert_success'
  , 'Add a success box'
  , '/../images/tick-circle.png'
  , function(c){ return [
      '[alert type="success"]',
        (c || 'Alert Message'),
      '[/alert]'
    ].join('<br />')}
  );
  
  /********************************************************
  * Labels and Badges
  *********************************************************/
  register_plugin(
    'label'
  , 'Add a label'
  , '/../images/tag-medium.png'
  , function(c){ return [
      '[label]',
        (c || 'Label Text'),
      '[/label]'
    ].join('')}
  );
  
  register_plugin(
    'badge'
  , 'Add a badge'
  , '/../images/notification-counter-02.png'
  , function(c){ return [
      '[badge]',
        (c || '2'),
      '[/badge]'
    ].join('')}
  );
  
  /********************************************************
  * Accordion
  *********************************************************/
  register_plugin(
    'accordion'
  , 'Add an accordion'
  , '/../images/ui-accordion.png'
  , [
      '[accordion]',
        '[panel title="Accordion Heading 1"]',
          'Content for Item 1',
        '[/panel]',
        '[panel title="Accordion Heading 2"]',
          'Content for Item 2',
        '[/panel]',
        '[panel title="Accordion Heading 3"]',
          'Content for Item 3',
        '[/panel]',
      '[/accordion]'
    ].join('<br />')
  );
  
  /********************************************************
  * Modal
  *********************************************************/
  var modal_count = 0;
  register_plugin(
    'modal'
  , 'Add an modal window'
  , '/../images/application-medium.png'
  , function(c){ return [
      '[modal id="modal-'+(++modal_count)+'"]',
        c || 'Modal Content',
      '[/modal]'
    ].join('<br />') }
  );
})();