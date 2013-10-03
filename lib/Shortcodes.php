<?php
class Fabs_Bootstrap_Shortcodes extends Snap_Wordpress_Plugin
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
  
  protected $index=0;
  protected $replacements = array();
  protected $replacement_shortcode = 'twitter_bootstrap_replacement_';
  
  public function __construct()
  {
    parent::__construct();
  }
  
  public function set_bootstrap_version($version)
  {
    $this->bootstrap_version = $version;
  }
  
  /**
   * We need to process content prior to the autop filter, but
   * without moving the default order of autop (otherwise it will
   * break shortcodes for other plugins, like Gravity Forms.)
   *
   * http://wpforce.com/prevent-wpautop-filter-shortcode/
   *
   * @wp.filter       ["acf_the_content","the_content"]
   * @wp.priority     7
   */
  public function process_content_before_autop( $content )
  {
    global $shortcode_tags;
    $orig_shortcode_tags = $shortcode_tags;
    $shortcode_tags = array();
    
    $this_shortcodes = array();
    
    // lets add all of our own shortcode tags
    $reflectionClass = new ReflectionClass( $this );
    foreach( $reflectionClass->getMethods( ReflectionMethod::IS_PUBLIC ) as $method ){
      $name = $method->getName();
      if( ($shortcode = $this->snap->method('wp.shortcode', false)) !== false ){
        $this_shortcodes[] = $name;
        //$this->_wp_add('shortcode', $name);
        add_shortcode( $name, array( &$this, 'shortcode') );
      }
    }
    
    $content = do_shortcode( $content );
    $shortcode_tags = $orig_shortcode_tags;
    foreach( $this->replacements as $tag => $c ){
      add_shortcode($tag, array(&$this, 'do_replacement'));
    }
    
    return trim($content);
    
  }
  
  public function shortcode( $atts, $content='', $tag='')
  {
    $fn = preg_replace('/\d+$/', '', $tag);
    ob_start();
    $this->$fn( $atts, $content, $tag );
    return $this->_shortcode( trim( ob_get_clean() ) );
  }
  
  protected function _wp_add($type, $name)
  {
    if( $type != 'shortcode' )
      parent::_wp_add($type, $name);
  }
  
  protected function _shortcode($content)
  {
    $tag = $this->replacement_shortcode.(++$this->index);
    $this->replacements[$tag] = $content;
    return "[$tag]";
  }
  
  public function do_replacement($atts=array(), $content='', $tag)
  {
    $ret = $this->replacements[$tag];
    unset( $this->replacements[$tag] );
    return do_shortcode( $ret );
  }
  
  /**
   * Return a string of html attributes from an associative array
   *
   * @param array Associative array of attributes
   * @return string HTML attribute string
   */
  protected function to_attrs( $ar )
  {
    $attrs = array();
    
    foreach( $ar as $key => $val ){
      if( !$key ) continue;
      if( strpos($key, 'data_') === 0 ){
        $key = 'data-'.substr($key, 5);
      }
      $val = esc_attr( $val );
      $attrs[] = "$key=\"$val\"";
    }
    return implode(' ', $attrs);
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
  public function row($attrs, $content='')
  {
    $attrs = (array) $attrs;
    
    $this->row_stack[] = $attrs+array('_shortcode'=>'row', '_items' => array());
    
    // this needs text
    $tag = 'div';
    extract( $attrs );
    
    add_shortcode('row'.count($this->row_stack), array(&$this,'shortcode'));
    add_shortcode('col'.count($this->row_stack), array(&$this,'shortcode'));
    
    $content = do_shortcode( trim($content) );
    
    remove_shortcode('row'.count($this->row_stack));
    remove_shortcode('col'.count($this->row_stack));
    
    $row = array_pop( $this->row_stack );
    
    $classes = array();
    $classes[] = $this->bootstrap_version == 3 || @$fixed ? 'row' : 'row-fluid';
    
    if( @$class ) $classes += explode( ' ', $class );
    
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
    
    $classes = array("span{$span}", "col-sm-{$span}");
    
    if( @$class ) $classes = array_merge( $classes, $explode(' ',$class));
    
    $tag_attrs = array(
      'class' => implode(' ', $classes)
    );
    
    foreach(array('class','tag','span') as $k ) unset( $attrs[$k] );
    $tag_attrs = array_merge( $attrs, $tag_attrs );
    
    ob_start();
    ?>
    <<?= $tag ?> <?= $this->to_attrs( $tag_attrs) ?>><?= do_shortcode( trim($content) ) ?></<?= $tag ?>>
    <?php
    $col['_content'] = trim( ob_get_clean() );
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
    
    do_shortcode( trim($content) );
    
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
    $nav = trim( ob_get_clean() );
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
    <div <?= $this->to_attrs( $tag_attrs ) ?>><?= wpautop( do_shortcode( trim( $content ) ) ) ?></div>
    <?php
    
    $item['_content'] = trim( ob_get_clean() );
    
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
    if( @$class ) $classes = array_merge( $classes, $explode(' ',$class));
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
      <?= do_shortcode( trim( $content ) ) ?>
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
    if( @$class ) $classes = array_merge( $classes, $explode(' ',$class));
    if( @$type ){
      $classes[] = $classes[] = 'label-'.$type;
    }
    $tag_attrs = array(
      'class' => implode(' ', $classes)
    );
    foreach(array('class','type') as $k) unset( $attrs[$k] );
    ?>
    <<?= $tag ?> <?= $this->to_attrs( $tag_attrs ) ?>>
      <?= do_shortcode( trim( $text ) ) ?>
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
    if( @$class ) $classes = array_merge( $classes, $explode(' ',$class));
    if( @$type ){
      $classes[] = $classes[] = 'badge-'.$type;
    }
    $tag_attrs = array(
      'class' => implode(' ', $classes)
    );
    foreach(array('class','type') as $k) unset( $attrs[$k] );
    ?><<?= $tag ?> <?= $this->to_attrs( $tag_attrs ) ?>><?= do_shortcode( trim( $text ) ) ?></<?= $tag ?>><?php
    
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
    
    if( @$class ) $classes = array_merge( $classes,  explode( ' ', $class ) );
    
    $tag_attrs = array(
      'class' => implode(' ', $classes)
    , 'id'    => $id
    );
    
    foreach(array('class','id','open') as $k ) unset( $attrs[$k] );
    $tag_attrs = array_merge( $attrs, $tag_attrs );
    
    $this->accordion = array();
    $content = do_shortcode( trim($content) );
    
    ?>
    <div <?= $this->to_attrs( $tag_attrs ) ?>>
    <?php
    foreach( $this->accordion as $i => $panel ){
      ?>
      <div class="accordion-group<?= $panel['open'] ? ' open':'' ?>">
        <div class="accordion-heading">
          <a class="accordion-toggle" data-toggle="collapse" data-parent="#<?= $id ?>" href="#<?= $panel['attrs']['id'] ?>">
            <?= $panel['title'] ?>
          </a>
        </div>
        <div id="<?= $panel['attrs']['id'] ?>" class="accordion-body collapse <?= $panel['open'] ? 'in' : 'out' ?>">
          <div class="accordion-inner"><?= $panel['content'] ?></div>
        </div>
      </div>
      <?php
    } 
    ?>
    </div>
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
    $content = wpautop( do_shortcode( trim( $content ) ) );
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
    
    $classes = array('modal', 'hide', 'fade');
    
    if( @$class ) $classes = array_merge( $classes, explode( ' ', $class ) );
    
    $tag_attrs = array(
      'class'     => implode(' ', $classes)
    , 'id'        => $id
    , 'tabindex'  => -1
    , 'role'      => 'dialog'
    );
    
    foreach(array('class','title') as $k ) unset( $attrs[$k] );
    $tag_attrs = array_merge( $attrs, $tag_attrs );
    
    ?>
    <div <?= $this->to_attrs( $tag_attrs ) ?>>
    <?php
      if( @$title ){
        ?>
      <div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3><?= $title ?></h3>
      </div>
        <?php
      }
      ?>
      <div class="modal-body">
      <?php
        echo wpautop( do_shortcode( trim( $content ) ) );
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
    ?>
    </div>
    <?php
    
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
    ?>
    <i <?= $this->to_attrs( $tag_attrs ) ?>></i>
    <?php
    
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
    
    $title =  do_shortcode( trim($content) );
    
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
    
    $content = do_shortcode( trim($content) );
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
    $this->title_stack[] = $content;
  }
}
