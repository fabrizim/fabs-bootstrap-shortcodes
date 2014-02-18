<?php
class Fabs_Bootstrap_Shortcodes extends Snap_Wordpress_Shortcodes
{
  
  protected $buttons = array(
    'button'
  , 'halves'
  , 'thirds'
  , 'tabs'
  , 'tabs_vertical'
  , 'accordion'
  , 'alert'
  , 'alert_error'
  , 'alert_success'
  , 'label'
  , 'badge'
  , 'modal'
  );
  
  protected $nav_stack = array();
  protected $row_stack = array();
  protected $title_stack = array();
  protected $id = 0;
  protected $in_shortcode = false;
  protected $bootstrap_version = 2;
  
  public function set_bootstrap_version( $version )
  {
    $this->bootstrap_version = $version;
  }
  
  /**
   * @wp.filter
   */
  public function mce_external_plugins( $plugin_array)
  {
    $js = plugins_url('assets/javascripts/tinymce.js', dirname(__FILE__).'/../index.php' );
    foreach( $this->buttons as $btn ) $plugin_array[$btn] = $js;
    return $plugin_array;
  }
  
  /**
   * @wp.filter
   */
  public function mce_buttons_3( $buttons )
  {
    return $buttons + $this->buttons;
  }
  
  /**
   * @wp.action
   */
  public function admin_head()
  {
    ?>
    <style type="text/css">
      .mceToolbarRow3 .mceIcon img.mceIcon {
        height: 16px;
        width: 16px;
        margin: 2px;
      }
    </style>
    <?php
  }
  
  
  /**
   * @wp.shortcode
   */
  public function button($attrs, $text = '')
  {
    
    $attrs = (array) $attrs;
    
    // defaults
    $tag = 'a';
    $type = 'primary';
    if( !@$text ) $text = 'Button';
    
    extract( $attrs );
    
    $classes = array('btn');
    
    if( @$class ) $classes = array_merge( $classes, explode( ' ', $class ) );
    
    // class shortcuts
    if( @$size ) $classes[] = 'btn-'.$size;
    if( @$type ) $classes[] = 'btn-'.$type;
    
    $tag_attrs = array(
      'class' => implode(' ',$classes)
    );
    
    if( @$tag == 'button' ) $tag_attrs['type'] = 'button';
    
    foreach(array('a','text','class','type','size') as $k ) unset( $attrs[$k] );
    $tag_attrs = array_merge( $attrs, $tag_attrs );
    
    
    ?>
    <<?= $tag ?> <?= $this->to_attrs( $tag_attrs ) ?>><?= do_shortcode($text) ?></<?= $tag ?>>
    <?php
    
  }
  
  /**
   * @wp.shortcode
   */
  public function row($attrs=array(), $content='', $tag)
  {
    $attrs = (array) $attrs;
    
    $this->row_stack[] = array_merge($attrs,array('_shortcode'=>'row', '_items' => array()));
    
    // this needs text
    $tag = 'div';
    extract( $attrs );
    
    add_shortcode('row'.count($this->row_stack), array(&$this,'shortcode'));
    add_shortcode('col'.count($this->row_stack), array(&$this,'shortcode'));
    
    $this->in_shortcode = $tag;
    $content = do_shortcode( $content );
    $this->in_shortcode = false;
    
    remove_shortcode('row'.count($this->row_stack));
    remove_shortcode('col'.count($this->row_stack));
    
    $row = array_pop( $this->row_stack );
    
    $classes = array();
    $classes[] = $this->bootstrap_version == 3 || @$fixed ? 'row' : 'row-fluid';
    
    if( @$class ) $classes = array_merge( $classes, explode( ' ', $class ));
    
    $tag_attrs = array(
      'class' => implode(' ', $classes)
    );
    
    foreach(array('class','fixed','tag') as $k ) unset( $attrs[$k] );
    $tag_attrs = array_merge( $attrs, $tag_attrs );
    
    
    ?>
    <<?= $tag ?> <?= $this->to_attrs( $tag_attrs ) ?>><?php foreach( $row['_items'] as $col ) echo $col['_content']; ?></<?= $tag ?>>
    <?php
    
  }
  
  /**
   * @wp.shortcode
   */
  public function col($attrs, $content='')
  {
    
    $attrs = (array) $attrs;
    
    // defaults
    $tag = 'div';
    $span = 6;
    
    // this needs text
    extract( $attrs );
    
    $row =& $this->row_stack[count($this->row_stack)-1];
    $col = $attrs;
    
    $classes = array();
    
    if( $this->bootstrap_version === 3 ){
      if( @$attrs['span'] && !@$sm) $classes[] = "col-sm-{$span}";
      if( @$attrs['offset'] && !@$sm_offset ) $classes[] = "col-sm-offset-{$offset}";
      
      foreach( array('xs','sm','md','lg') as $size ){
        
        if( @$$size ) $classes[] = "col-{$size}-{$$size}";
        unset( $attrs[$size] );
        
        // offset
        $offset = "{$size}_offset";
        if( @$$offset ) $classes[] = "col-{$size}-offset-{$$offset}";
        unset( $attrs[$offset] );
        
        // push
        $push = "{$size}_push";
        if( @$$push ) $classes[] = "col-{$size}-push-{$$push}";
        unset( $attrs[$push] );
        
        // push
        $pull = "{$size}_pull";
        if( @$$pull ) $classes[] = "col-{$size}-pull-{$$pull}";
        unset( $attrs[$pull] );
        
      }
    }
    else {
      $classes[] = "span{$span}";
      if( @$offset ){
        $classes[] = "offset{$offset}";
      }
    }
    if( @$class ) $classes = array_merge( $classes, explode(' ',$class));
    
    $tag_attrs = array(
      'class' => implode(' ', $classes)
    );
    
    foreach(array('class','tag','span','xs','sm','md','lg') as $k ) unset( $attrs[$k] );
    $tag_attrs = array_merge( $attrs, $tag_attrs );
    
    ob_start();
    ?>
    <<?= $tag ?> <?= $this->to_attrs( $tag_attrs) ?>>
    <?= do_shortcode( $content ) ?>
    </<?= $tag ?>>
    <?php
    $col['_content'] = ob_get_clean();
    $row['_items'][] = $col;
  }
  
  /**
   * @wp.shortcode
   */
  public function nav($attrs, $content='', $tag)
  {
    
    $attrs = (array) $attrs;
    
    $this->nav_stack[] = $attrs+array('_shortcode'=>'nav','_items' => array());
    
    // this needs text
    extract( $attrs );
    
    add_shortcode('nav'.count($this->nav_stack), array(&$this,'shortcode'));
    add_shortcode('item'.count($this->nav_stack), array(&$this,'shortcode'));
    
    do_shortcode( $content );
    
    remove_shortcode('nav'.count($this->nav_stack));
    remove_shortcode('item'.count($this->nav_stack));
    
    $cur = array_pop( $this->nav_stack );
    
    $classes = array();
    $nav_classes = array("nav");
    
    if( @$class ) $classes += explode( ' ', $class );
    if( @$type ){
      $nav_classes[] = 'nav-'.$type;
    }
    $classes[] = 'tabbable';
    if( @$tabs ) $classes[] = 'tabs-'.$tabs;
    
    $tag_attrs = array(
      'class' => implode(' ', $classes)
    );
    
    foreach(array('class','tag','type','tabs') as $k ) unset( $attrs[$k] );
    $tag_attrs = array_merge( $attrs, $tag_attrs );
    
    ob_start();
    ?>
    <ul class="<?= implode(' ',$nav_classes) ?>">
      <?php
      foreach( $cur['_items'] as $i => $item ){
        $item_attrs = array(
          'href'  => '#'.$item['id']
        );
        $item_attrs['data-toggle'] = 'tab';
        ?>
      <li class="<?= $i===0 ? 'active' : '' ?>">
        <a <?= $this->to_attrs( $item_attrs ) ?>><?= @$item['title'] ? $item['title'] : ('Item '.($i+1)) ?></a>
      </li>
        <?php
      }
      ?>
    </ul>
    <?php
    $nav = ob_get_clean();
    ?>
    <div <?= $this->to_attrs( $tag_attrs ) ?>>
      <?php
      if( @$tabs != 'below' ){ 
        echo $nav;
      } 
      ?>
      <div class="tab-content">
      <?php
        foreach( $cur['_items'] as $item )  echo $item['_content'];
      ?>
      </div>
      <?php
      if( @$tabs == 'below' ){
        echo $nav;
      } 
    ?>
    </div>
    <?php
    
  }
  
  /**
   * @wp.shortcode
   */
  public function item($attrs, $content='', $tag='')
  {
    
    $attrs = (array) $attrs;
    
    if( !@$attrs['id'] ) $attrs['id'] = 'tbs_nav_item_'.($this->id++);
    
    // this needs text
    extract( $attrs );
    $nav =& $this->nav_stack[count($this->nav_stack)-1];
    $cur = count( $nav['_items'] );
    $item = $attrs;
    
    $classes = array();
    
    if( @$class ) $classes = array_merge( $classes, explode( ' ', $class ) );
    if( $cur == 0 ) $classes[] = 'active';
    
    $classes[] = 'tab-pane';
    
    $tag_attrs = array(
      'class'   => implode(' ', $classes)
    , 'id'      => $id
    );
    
    foreach(array('class','active','title') as $k ) unset( $attrs[$k] );
    $tag_attrs = array_merge( $attrs, $tag_attrs );
    
    ob_start();
    ?>
    <div <?= $this->to_attrs( $tag_attrs ) ?>><?= do_shortcode( $content ) ?></div>
    <?php
    
    $item['_content'] = ob_get_clean();
    
    $count = count( $this->title_stack );
    if( count($this->title_stack) > $count ){
      $item['title'] = array_pop($this->title_stack);
    }
    
    $nav['_items'][] = $item;
  }
  
  /**
   * @wp.shortcode
   */
  public function alert($attrs, $content='')
  {
    $attrs = (array) $attrs;
    extract( $attrs );
    $classes = array('alert');
    if( @$class ) $classes = array_merge( $classes, explode(' ',$class));
    if( @$type ){
      $classes[] = $classes[] = 'alert-'.$type;
    }
    if( @$block ){
      $classes[] = 'alert-block';
    }
    $tag_attrs = array(
      'class' => implode(' ', $classes)
    );
    foreach(array('class','type','block') as $k) unset( $attrs[$k] );
    
    ?>
    <div <?= $this->to_attrs( $tag_attrs ) ?>>
      <?php if( @$close ){ ?><button type="button" class="close" data-dismiss="alert">&times;</button><?php } ?>
      <?= do_shortcode( $content ) ?>
    </div>
    <?php
    
  }
  
  /**
   * @wp.shortcode
   */
  public function label($attrs, $text='')
  {
    $attrs = (array) $attrs;
    $tag = 'span';
    extract( $attrs );
    $classes = array('label');
    if( @$class ) $classes = array_merge( $classes, explode(' ',$class));
    if( @$type ){
      $classes[] = $classes[] = 'label-'.$type;
    }
    $tag_attrs = array(
      'class' => implode(' ', $classes)
    );
    foreach(array('class','type') as $k) unset( $attrs[$k] );
    ?>
    <<?= $tag ?> <?= $this->to_attrs( $tag_attrs ) ?>>
      <?= do_shortcode( $text ) ?>
    </<?= $tag ?>>
    <?php
    
  }
  
  /**
   * @wp.shortcode
   */
  public function badge($attrs, $text='')
  {
    $attrs = (array) $attrs;
    $tag = 'span';
    extract( $attrs );
    $classes = array('badge');
    if( @$class ) $classes = array_merge( $classes, explode(' ',$class));
    if( @$type ){
      $classes[] = $classes[] = 'badge-'.$type;
    }
    $tag_attrs = array(
      'class' => implode(' ', $classes)
    );
    foreach(array('class','type') as $k) unset( $attrs[$k] );
    ?><<?= $tag ?> <?= $this->to_attrs( $tag_attrs ) ?>><?= do_shortcode( $text ) ?></<?= $tag ?>><?php
    
  }
  
  /**
   * @wp.shortcode
   */
  public function accordion($attrs, $content='')
  {
    
    $attrs = (array) $attrs;
    if( !@$attrs['id'] ) $attrs['id'] = 'accordion'.(++$this->id);
    
    // this needs text
    extract( $attrs );
    
    $classes = array();
    $bs3 = $this->bootstrap_version === 3;
    
    if( @$class ) $classes = array_merge( $classes,  explode( ' ', $class ) );
    
    if( $bs3 ) $classes[] = 'panel-group';
    
    $tag_attrs = array(
      'class' => implode(' ', $classes)
    , 'id'    => $id
    );
    
    
    foreach(array('class','id','open') as $k ) unset( $attrs[$k] );
    $tag_attrs = array_merge( $attrs, $tag_attrs );
    
    $this->accordion = array();
    $content = do_shortcode( $content );
    
    
    
    ?>
    <ul <?= $this->to_attrs( $tag_attrs ) ?>>
    <?php
    foreach( $this->accordion as $i => $panel ){
      ?>
      <li class="<?= $bs3?'panel panel-default':'accordion-group' ?><?= $panel['open'] ? ' open':'' ?>">
        <div class="<?= $bs3?'panel':'accordion' ?>-heading"><h4 class="<?= $bs3?'panel-title':'accordion-title'?>"><a class="accordion-toggle" data-toggle="collapse" data-parent="#<?= $id ?>" href="#<?= $panel['attrs']['id'] ?>"><?= $panel['title'] ?></a></h4></div>
        <div id="<?= $panel['attrs']['id'] ?>" class="<?=$bs3?'panel-collapse':'accordion-body'?> collapse <?= $panel['open'] ? 'in' : 'out' ?>">
          <div class="<?=$bs3?'panel-body':'accordion-inner'?>"><?= $panel['content'] ?></div>
        </div>
      </li>
      <?php
    } 
    ?>
    </ul>
    <?php
    
  }
  
  /**
   * @wp.shortcode
   */
  public function panel($attrs, $content='')
  {
    
    $attrs = (array) $attrs;
    if( !@$attrs['id'] ) $attrs['id'] = 'accordion-panel-'.(++$this->id);
    extract( $attrs );
    
    $classes = array();
    
    if( @$class ) $classes = array_merge( $classes, explode( ' ', $class ) );
    
    $tag_attrs = array(
      'class'   => implode(' ', $classes)
    );
    
    foreach(array('class','title','open') as $k ) unset( $attrs[$k] );
    $tag_attrs = array_merge( $attrs, $tag_attrs );
    
    $count = count( $this->title_stack );
    $content = do_shortcode( $content );
    if( count($this->title_stack) > $count ){
      $title = array_pop($this->title_stack);
    }
    
    
    $this->accordion[] = array(
      'title'   => @$title ? $title : 'Accordion Title '.(count($this->accordion)+1)
    , 'content' => $content
    , 'attrs'   => $tag_attrs
    , 'open'    => @$open
    );
  }
  
  /**
   * @wp.shortcode
   */
  public function modal($attrs, $content='')
  {
    
    $attrs = (array) $attrs;
    if( !@$attrs['id'] ) $attrs['id'] = 'modal-'.(++$this->id);
    extract( $attrs );
    
    $classes = array('modal', 'fade');
    if( $this->bootstrap_version == 2 ) $classes[] = 'hide';
    
    if( @$class ) $classes = array_merge( $classes, explode( ' ', $class ) );
    
    $tag_attrs = array(
      'class'     => implode(' ', $classes)
    , 'id'        => $id
    , 'tabindex'  => -1
    , 'role'      => 'dialog'
    );
    
    foreach(array('class','title') as $k ) unset( $attrs[$k] );
    $tag_attrs = array_merge( $attrs, $tag_attrs );
    ob_start();
    ?>
    <div <?= $this->to_attrs( $tag_attrs ) ?>>
    <?php
      if( $this->bootstrap_version === 3 ){
        ?>
        <div class="modal-dialog"><div class="modal-content">
        <?php
      }
      if( @$title ){
        ?>
      <div class="modal-header">
        <div><button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button></div>
        <h3><?= $title ?></h3>
      </div>
        <?php
      }
      ?>
      <div class="modal-body">
      <?php
        echo apply_filters('the_content', $content );
      ?>
      </div>
      <?php
      
      if( @$footer !== 'false' && @$footer !== false ){
        ?>
      <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
      </div>
        <?php
      }
      if( $this->bootstrap_version === 3 ){
        ?>
        </div></div>
        <?php
      }
    ?>
    </div>
    <?php
    if( !is_array(@$GLOBALS['_snap_modals']) ) $GLOBALS['_snap_modals'] = array();
    $GLOBALS['_snap_modals'][] = ob_get_clean();
    
  }
  
  /**
   * @wp.action         wp_footer
   */
  public function output_modals()
  {
    if( is_array(@$GLOBALS['_snap_modals']) ) foreach( $GLOBALS['_snap_modals'] as $modal ) echo $modal;
  }
  
  /**
   * @wp.shortcode
   */
  public function modal_link($attrs=array(), $content='', $tag)
  {
    $attrs = (array) $attrs;
    extract( $attrs );
    $classes = array();
    if( @$class ) $classes = array_merge( $classes, explode(' ',$class));
    if( !@$slug ){
      $slug = @$page;
    }
    if( !$slug ) return;
    $page = get_page_by_path( $slug );
    $tag_attrs = array(
      'class' => implode(' ', $classes)
    , 'href'  => '#'.$page->post_name
    , 'data-toggle' => 'modal'
    );
    foreach(array('class','slug','page') as $k) unset( $attrs[$k] );
    $tag_attrs = array_merge( $attrs, $tag_attrs );
    ?><a <?= $this->to_attrs( $tag_attrs ) ?>><?= $content ?></a><?php
    global $post;
    $post = $page;
    setup_postdata( $post );
    $attrs['id'] = $page->post_name;
    if( !@$title ) $attrs['title'] = get_the_title();
    $this->modal( $attrs, get_the_content() );
    wp_reset_postdata();
  }
  
  /**
   * @wp.shortcode
   */
  public function icon($attrs, $content='', $tag)
  {
    
    $attrs = (array) $attrs;
    extract( $attrs );
    
    $classes = array();
    if( @$icon ) $classes[] = "icon-$icon";
    if( @$class ) $classes = array_merge( $classes, explode( ' ', $class ) );
    
    $tag_attrs = array(
      'class'   => implode(' ', $classes)
    );
    
    foreach(array('class','icon') as $k ) unset( $attrs[$k] );
    $tag_attrs = array_merge( $attrs, $tag_attrs );
    ?><i <?= $this->to_attrs( $tag_attrs ) ?>></i><?php
    
  }
  
  /**
   * @wp.shortcode
   */
  public function tooltip($attrs, $content='')
  {
    
    $attrs = (array) $attrs;
    extract( $attrs );
    
    if( !(@$text || @$icon) ) return;
    
    $this->register_tooltip_script();
    
    $classes = array('display-tooltip');
    if( @$class ) $classes = array_merge( $classes, explode( ' ', $class ) );
    if( @$icon ) {
      $tag = 'i';
      $classes = array_merge( $classes, array('icon', 'icon-'.$icon) );
      $text = '';
    }
    else {
      $tag = 'span';
    }
    
    $title =  do_shortcode( $content );
    
    if( !@$placement ) $placement = 'top';
    
    $tag_attrs = array(
      'data-has-tooltip'=> 'true',
      'class'           => implode(' ', $classes),
      'title'           => $title,
      'data-placement'  => $placement
    );
    
    foreach(array('class','icon','text') as $k ) unset( $attrs[$k] );
    $tag_attrs = array_merge( $attrs, $tag_attrs );
    ?>
    <<?= $tag ?> <?= $this->to_attrs( $tag_attrs ) ?>><?= $text ?></<?= $tag ?>>
    <?php
    
  }
  
  /**
   * @wp.shortcode
   */
  public function popover($attrs, $content='')
  {
    
    $attrs = (array) $attrs;
    extract( $attrs );
    
    if( !(@$text || @$icon) ) return;
    
    $this->register_popover_script();
    
    $classes = array('display-popover');
    if( @$class ) $classes = array_merge( $classes, explode( ' ', $class ) );
    if( @$icon ) {
      $tag = 'i';
      $classes = array_merge( $classes, array('icon', 'icon-'.$icon) );
      $text = '';
    }
    else {
      $tag = 'span';
    }
    
    $content = do_shortcode( $content );
    if( !@$title ) $title = false;
    
    if( !@$placement ) $placement = 'top';
    
    $tag_attrs = array(
      'data-has-popover'=> 'true',
      'class'           => implode(' ', $classes),
      'data-content'    => $content,
      'data-placement'  => $placement,
      'title'           => $title,
      'data-html'       => 'true'
    );
    
    foreach(array('class','icon','text') as $k ) unset( $attrs[$k] );
    $tag_attrs = array_merge( $attrs, $tag_attrs );
    ?>
    <<?= $tag ?> <?= $this->to_attrs( $tag_attrs ) ?>><?= $text ?></<?= $tag ?>>
    <?php
    
  }
  
  protected function register_tooltip_script()
  {
    static $registered;
    if( !isset($registered) ){
      add_action('wp_footer', function(){
        ?>
        <script type="text/javascript">
          jQuery('[data-has-tooltip]').tooltip();
        </script>
        <?php
      });
      $registered = true;
    }
  }
  
  protected function register_popover_script()
  {
    static $registered;
    if( !isset($registered) ){
      add_action('wp_footer', function(){
        ?>
        <script type="text/javascript">
          jQuery('[data-has-popover]').popover({
            trigger: 'manual'
          }).on('click', function(){
            
            if ( jQuery(this).data('popover_displayed') ) return;
            
            var $this = jQuery(this);
            
            $this.data('popover_displayed', true);
            $this.popover('show');
            
            // prevent same event from triggering close
            setTimeout(function(){
              jQuery(document).on('click', document_click);
              jQuery(document).on('keyup', document_keyup);
            }, 10);
            
            function document_click(e){
              if ( jQuery(e.target).parents('.popover').length ) return;
              hide();
            }
            
            function document_keyup(e){
              // listen for escape
              if ( e.keyCode == 27 ) hide();
            }
            
            function hide() {
              $this.popover('hide');
              $this.data('popover_displayed', false);
              jQuery( document ).off('click', document_click);
              jQuery( document ).off('keyup', document_keyup);
            }
            
          });
        </script>
        <?php
      });
      $registered = true;
    }
  }
  /**
   * wp.shortcode
   */
  public function esc($attrs, $content='')
  {
    echo str_replace(array('[',']'), array('&#91;','&#93;'), $content);
  }
  
  /**
   * This is a multi-purpose shortcode to use in place of an attribute
   * for nav and accordion components
   * 
   * @wp.shortcode
   */
  public function title($attrs = array(), $content = '')
  {
    $this->title_stack[] = do_shortcode($content);
  }
}
