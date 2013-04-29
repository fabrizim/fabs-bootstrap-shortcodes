<?
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
  
  public function __construct()
  {
    parent::__construct();
  }
  
  /**
   * We need to process content prior to the autop filter, but
   * without moving the default order of autop (otherwise it will
   * break shortcodes for other plugins, like Gravity Forms.)
   *
   * http://wpforce.com/prevent-wpautop-filter-shortcode/
   *
   * @wp.filter       the_content
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
        $this->_wp_add('shortcode', $name);
      }
    }
    
    $content = do_shortcode( $content );
    /*
    foreach( $orig_shortcode_tags as $key => $fn){
      if( in_array($key, $this_shortcodes ) ) unset( $orig_shortcode_tags[$key] );
    }
    */
    $shortcode_tags = $orig_shortcode_tags;
    
    return $content;
    
  }
  
  protected function _wp_register_methods()
  {
    $reflectionClass = new ReflectionClass( $this );
    foreach( $reflectionClass->getMethods( ReflectionMethod::IS_PUBLIC ) as $method ){
      $name = $method->getName();
      // check to see if its a shortcode
      if( ($shortcode = $this->snap->method('wp.shortcode', false)) !== false ){
        // if we have a prefix for the shortcode, lets add it here
        
        //$this->snap->registry()->set("method.$name.wp.shortcode", "{$prefix}_{$name}");
      }
    }
    
    return parent::_wp_register_methods();
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
    <?
  }
  
  
  /**
   * @wp.shortcode
   */
  public function button($attrs, $text = '')
  {
    
    $attrs = (array) $attrs;
    
    // defaults
    $tag = 'a';
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
    
    ob_start();
    ?><<?= $tag ?> <?= $this->to_attrs( $tag_attrs ) ?>><?= $text ?></<?= $tag ?>><?
    return ob_get_clean();
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
    
    add_shortcode('row'.count($this->row_stack), array(&$this,'row'));
    add_shortcode('col'.count($this->row_stack), array(&$this,'col'));
    
    $content = do_shortcode( trim($content) );
    
    remove_shortcode('row'.count($this->row_stack));
    remove_shortcode('col'.count($this->row_stack));
    
    $row = array_pop( $this->row_stack );
    
    $classes = array();
    $classes[] = @$fixed ? 'row' : 'row-fluid';
    
    if( @$class ) $classes += explode( ' ', $class );
    
    $tag_attrs = array(
      'class' => implode(' ', $classes)
    );
    
    foreach(array('class','fixed','tag') as $k ) unset( $attrs[$k] );
    $tag_attrs = array_merge( $attrs, $tag_attrs );
    
    ob_start();
    ?>
    <<?= $tag ?> <?= $this->to_attrs( $tag_attrs ) ?>><?
      foreach( $row['_items'] as $col ) echo $col['_content'];
    ?></<?= $tag ?>>
    <?
    return trim( ob_get_clean() );
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
    
    $classes = array("span{$span}");
    
    if( @$class ) $classes = array_merge( $classes, $explode(' ',$class));
    
    $tag_attrs = array(
      'class' => implode(' ', $classes)
    );
    
    foreach(array('class','tag') as $k ) unset( $attrs[$k] );
    $tag_attrs = array_merge( $attrs, $tag_attrs );
    
    ob_start();
    ?>
    <<?= $tag ?> <?= $this->to_attrs( $tag_attrs) ?>><?= do_shortcode( trim($content) ) ?></<?= $tag ?>>
    <?
    $col['_content'] = trim( ob_get_clean() );
    $row['_items'][] = $col;
  }
  
  /**
   * @wp.shortcode
   */
  public function nav($attrs, $content='')
  {
    
    $attrs = (array) $attrs;
    
    $this->nav_stack[] = $attrs+array('_shortcode'=>'nav','_items' => array());
    
    // this needs text
    extract( $attrs );
    
    add_shortcode('nav'.count($this->nav_stack), array(&$this,'nav'));
    add_shortcode('item'.count($this->nav_stack), array(&$this,'item'));
    
    $content = do_shortcode( trim($content) );
    
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
      <? foreach( $cur['_items'] as $i => $item ){
        $item_attrs = array(
          'href'  => '#'.$item['id']
        );
        $item_attrs['data-toggle'] = 'tab';
        ?>
      <li class="<?= $i===0 ? 'active' : '' ?>">
        <a <?= $this->to_attrs( $item_attrs ) ?>><?=
          @$item['title'] ? $item['title'] : ('Item '.($i+1))
        ?></a>
      </li>
      <? } ?>
    </ul>
    <?
    $nav = ob_get_clean();
    
    ob_start();
    ?>
    <div <?= $this->to_attrs( $tag_attrs ) ?>>
      <? if( @$tabs != 'below' ){ ?>
      <?= $nav ?>
      <? } ?>
      <div class="tab-content"><?
        foreach( $cur['_items'] as $item )  echo $item['_content'];
      ?></div>
      <? if( @$tabs == 'below' ){ ?>
      <?= $nav ?>
      <? } ?>
    </div>
    <?
    return ob_get_clean();
    
  }
  
  /**
   * @wp.shortcode
   */
  public function item($attrs, $content='')
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
    <div <?= $this->to_attrs( $tag_attrs ) ?>><?= do_shortcode( trim( $content ) ) ?></div>
    <?
    $item['_content'] = ob_get_clean();
    
    $count = count( $this->title_stack );
    do_shortcode( trim( $content ) );
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
    ob_start();
    ?>
    <div <?= $this->to_attrs( $tag_attrs ) ?>>
      <? if( @$close ){ ?><button type="button" class="close" data-dismiss="alert">&times;</button><? } ?>
      <?= do_shortcode( trim( $content ) ) ?>
    </div>
    <?
    return ob_get_clean();
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
    ob_start();
    ?><<?= $tag ?> <?= $this->to_attrs( $tag_attrs ) ?>><?= do_shortcode( trim( $text ) ) ?></<?= $tag ?>><?
    return ob_get_clean();
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
    ob_start();
    ?><<?= $tag ?> <?= $this->to_attrs( $tag_attrs ) ?>><?= do_shortcode( trim( $text ) ) ?></<?= $tag ?>><?
    return ob_get_clean();
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
    
    ob_start();
    ?><div <?= $this->to_attrs( $tag_attrs ) ?>>
      <? foreach( $this->accordion as $i => $panel ){ ?>
        <div class="accordion-group"><?
        ?><div class="accordion-heading"><?
          ?><a class="accordion-toggle" data-toggle="collapse" data-parent="#<?= $id ?>" href="#<?= $panel['attrs']['id'] ?>"><?= $panel['title'] ?></a><?
        ?></div><?
        ?><div id="<?= $panel['attrs']['id'] ?>" class="accordion-body collapse <?= !$i && @$open ? 'in' : 'out' ?>"><?
          ?><div class="accordion-inner"><?= $panel['content'] ?></div><?
        ?></div><?
      ?></div><?
      } 
    ?></div>
    <?
    return ob_get_clean();
    
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
    
    foreach(array('class','title') as $k ) unset( $attrs[$k] );
    $tag_attrs = array_merge( $attrs, $tag_attrs );
    
    $count = count( $this->title_stack );
    do_shortcode( trim( $content ) );
    if( count($this->title_stack) > $count ){
      $title = array_pop($this->title_stack);
    }
    
    
    $this->accordion[] = array(
      'title'   => @$title ? $title : 'Accordion Title '.(count($this->accordion)+1)
    , 'content' => $content
    , 'attrs'   => $tag_attrs
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
    ob_start();
    ?>
    <div <?= $this->to_attrs( $tag_attrs ) ?>>
      <? if( @$title ){ ?>
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3><?= $title ?></h3>
      </div>
      <? } ?>
      
      <div class="modal-body">
        <?= do_shortcode( trim( $content ) ) ?>
      </div>
      
      <? if( @$footer !== 'false' && @$footer !== false ){ ?>
      <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
      </div>
      <? } ?>
      
    </div>
    <?
    return ob_get_clean();
  }
  
  /**
   * @wp.shortcode
   */
  public function icon($attrs, $content='')
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
    ob_start();
    ?>
    <i <?= $this->to_attrs( $tag_attrs ) ?>></i>
    <?
    return ob_get_clean();
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
