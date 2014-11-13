jQuery(function($){
  
  var converted = false,
      navs = []
  
  function convert()
  {
    $('.tabbable').each(function(){
      var $ct = $(this);
      if( !$ct.data('accordion') ){
        
        // create an accordion...
        var $accordion = $('<div />')
              .addClass('panel-group');
        
        var $tabs =  $ct.find('> .nav > li'),
            $contents = $ct.find('> .tab-content > .tab-pane')
        
        $tabs.each(function(i, tab){
          var $tab = $(tab);
          
          // create a panel
          var $panel = $('<div />')
                .addClass('panel panel-default')
                .addClass($tab.hasClass('active')?'toggle-open':''),
              
              $heading = $('<div />')
                .addClass('panel-heading')
                .appendTo($panel),
                
              $title = $('<h4 />')
                .addClass('panel-title')
                .appendTo($heading),
                
              $toggle = $('<a />')
                .addClass('accordion-toggle')
                .attr('data-toggle','collapse')
                .attr('href', $tab.find('a').attr('href'))
                .html($tab.find('a').html())
                .appendTo( $title ),
              
              $body = $('<div />')
                .attr( 'id', $contents.eq(i).attr('id') )
                .addClass('panel-collapse collapse')
                .addClass($tab.hasClass('active')?'in':'out')
                .appendTo( $panel ),
              
              $content = $('<div  />')
                .addClass('panel-body')
                .html( $contents.eq(i).html() )
                .appendTo( $body )
          
          $panel.appendTo( $accordion );
          
        });
      
        $ct.data('accordion', $accordion);
      }
      $ct.data('accordion').insertBefore($ct);
      $ct.detach();
      navs.push($ct);
    });
    converted = true;
  }
  
  function revert()
  {
    $.each(navs, function(i, $ct){
      $ct.insertBefore($ct.data('accordion'));
      $ct.data('accordion').detach();
    });
    navs = [];
    converted = false;
  }
  
  function onResize()
  {
    if ( $(window).width() < 768 && !converted) {
      convert();
    }
    else if ( $(window).width() >= 768 && converted) {
      revert();
    }
  }
  
  $(window).on('resize', onResize);
  onResize();
});