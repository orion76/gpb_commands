<?php

namespace Drupal\gpb_commands\Command;

use Drupal;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Utils\Validator;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Menu\MenuTreeStorageInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Generator\GeneratorInterface;
use function array_filter;
use function array_flip;
use function array_map;
use function explode;
use function str_replace;
use function substr;

/**
 * Class GenerateViewsCommand.
 *
 * Drupal\Console\Annotations\DrupalCommand (
 *     extension="gpb_commands",
 *     extensionType="module"
 * )
 */
class GenerateViewsCommand extends Command {

  use ModuleTrait;

  /**
   * Drupal\Console\Core\Generator\GeneratorInterface definition.
   *
   * @var \Drupal\Console\Core\Generator\GeneratorInterface
   */
  protected $generator;

  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * @var StringConverter
   */
  protected $stringConverter;


  protected MenuLinkManagerInterface $menuManager;

  protected MenuTreeStorageInterface $menuStorage;

  protected Manager $extensionManager;

  /**
   * @var Validator
   */
  protected $validator;

  protected RendererInterface $renderer;
  
  /**
   * Constructs a new GenerateViewsCommand object.
   */
  public function __construct(GeneratorInterface $gpb_commands_generate_views_generator,
                              EntityTypeManagerInterface $entityTypeManager,
                              StringConverter $stringConverter,
                              MenuLinkManagerInterface $menuManager,
                              MenuTreeStorageInterface $menuStorage,
                              Manager $extensionManager,
                              Validator $validator,
                              RendererInterface $renderer) {

    $this->generator = $gpb_commands_generate_views_generator;
    $this->entityTypeManager = $entityTypeManager;
    $this->stringConverter = $stringConverter;
    $this->menuManager = $menuManager;
    $this->menuStorage = $menuStorage;
    $this->extensionManager = $extensionManager;
    $this->validator = $validator;
    $this->renderer = $renderer;
    parent::__construct();
  }

  protected function configure() {


    $this
      ->setName('generate:views')
      ->setDescription($this->trans('commands.generate.views.description'))
      ->setHelp($this->trans('commands.generate.views.help'))
      /**
       * module
       * entity_type
       * views_page_path
       *
       * menu_name
       * menu_parent
       *
       */
      ->addOption(
        'module',
        NULL,
        InputOption::VALUE_REQUIRED,
        $this->trans('commands.common.options.module')
      )
      ->addOption(
        'entity-type',
        NULL,
        InputOption::VALUE_REQUIRED,
        $this->trans('commands.generate.views.options.entity-type')
      )
      ->addOption(
        'views-page-path',
        NULL,
        InputOption::VALUE_REQUIRED,
        $this->trans('commands.generate.views.options.views-page-path')
      )
      ->addOption(
        'menu-root',
        NULL,
        InputOption::VALUE_REQUIRED,
        $this->trans('commands.generate.views.options.menu-root')
      )
      ->addOption(
        'menu-parent',
        NULL,
        InputOption::VALUE_REQUIRED,
        $this->trans('commands.generate.views.options.menu-parent')
      )
      ->setAliases(['gv']);
  }


  protected function getMenuRootOptions() {
    $menus = $this->entityTypeManager->getStorage('menu')->loadMultiple();
    $options = [];
    /** @var \Drupal\system\MenuInterface[] $menus */
    foreach ($menus as $menu) {
      $options[$menu->id()] = $menu->label();
    }
    return $options;
  }

  protected function getMenuRootOption() {

    $input = $this->getIo()->getInput();

    $menu_root = $input->getOption('menu-root');
    if (!$menu_root) {
      $menus = $this->getMenuRootOptions();
      $menu_root = $this->getIo()->choice(
        $this->trans('commands.generate.views.menu-root'),
        $menus,
        function ($menu_root) {
          return TRUE;
        }
      );

      $input->setOption('menu-root', $menu_root);
    }

  }

  protected function getEntityTypeOptions() {
    $entity_types = array_filter($this->entityTypeManager->getDefinitions(),
      function (EntityTypeInterface $entity_type) {
        return $entity_type->getGroup() === 'content';
      });

    return array_map(function (EntityTypeInterface $entity_type) {
      return $entity_type->getLabel();
    }, $entity_types);

  }

  protected function getEntityTypeOption() {

    $input = $this->getIo()->getInput();

    $entity_type = $input->getOption('entity-type');
    if (!$entity_type) {
      $entity_types = $this->getEntityTypeOptions();
      $entity_type = $this->getIo()->choice(
        $this->trans('commands.generate.views.entity-type'),
        $entity_types,
        function ($menu_root) {
          return TRUE;
        }
      );

      $input->setOption('entity-type', $entity_type);
    }

  }


  protected function getMenuParentOptions($menu_root, $parent = NULL) {

    $properties = ['menu_name' => $menu_root];
    if (!is_null($parent)) {
      $properties['parent'] = $parent;
    }
    $menus = $this->menuStorage->loadByProperties($properties);
    return array_map(function ($menu) {
      return $menu['title'];
    }, $menus);
  }

  protected function loadMenuTree($menu_root) {
    $data = $this->menuStorage->loadByProperties(['menu_name' => $menu_root]);
    $tree = [];
    foreach ($data as $menu_id => $menu_data) {
      $tree[$menu_id] = [
        'id' => $menu_id,
        'title' => $menu_data['title'],
        'parent_id' => $menu_data['parent'],
        'parent' => NULL,
        'children' => [],
      ];
    }

    foreach ($tree as &$item) {
      if (!empty($item['parent_id'])) {
        $parent =& $tree[$item['parent_id']];
        $parent['children'][] = &$item;
      }
      $item['parent'] =& $parent;
    }

    $tree = array_filter($tree, function ($item) {
      return empty($item['parent']);
    });
    if (empty($tree)) {
      return [];
    }
    $root = reset($tree);
    return $root['children'];
  }

  protected function choiceTree($tree) {

    $current_tree = $tree;
    $action = NULL;
    $index = 0;
    $actions = [
      'select' => 'Select',
      'next' => 'Next',
      'prev' => 'Prev',
    ];
    do {

      $message='TODO';
      
      switch ($action) {
        case 'next':
          $current_tree = $current_tree[$index]['children'];
          $parent_title = $current_tree[$index]['title'];
          $message = $this->trans('commands.generate.views.messages.select-children-menu') . "{$parent_title}";
          break;

        case 'prev':
          $current_tree = $current_tree[$index]['parent']['children'];
          $parent_title = $current_tree[$index]['parent']['title'];
          $message = $this->trans('commands.generate.views.messages.select-children-menu') . "{$parent_title}";
          break;

      }

      $links_curr = $this->getTreeOptions($current_tree);
      if (!empty($links_curr)) {
        $links = $links_curr;
      }

      $title = $this->getIo()->choice(
        $message,
        $links,
        0
      );

      $index=$this->getSelectedKey($title,$links);
      
      $action = $this->getIo()->choice(
        $this->trans('commands.generate.views.questions.select-children'),
        $actions,
        'select'
      );

    } while ($action !== 'select');
    return $current_tree[$index]['id'];
  }

private function getSelectedKey($value,$choices){
  $values = array_flip($choices);
  return $values[$value];
}
  protected function getTreeOptions($tree) {
    return array_map(function ($item) {
      return (string)$item['title'];
      }, $tree);
  }

  protected function getMenuParentOption() {

    $input = $this->getIo()->getInput();


    $menu_parent = $input->getOption('menu-parent');

    $action = NULL;

    if (!$menu_parent) {
      $menu_root = $input->getOption('menu-root');
      $tree = $this->loadMenuTree($menu_root);
      $menu_parent = $this->choiceTree($tree);
      $input->setOption('menu-parent', $menu_parent);
    }

  }

  protected function getViewPathOption() {

    $input = $this->getIo()->getInput();

    $base_path = $input->getOption('views-page-path');
    if (!$base_path) {
      $base_path = $this->getIo()->ask(
        $this->trans('commands.generate.views.questions.views-page-path'),
        $base_path
      );
      if (substr($base_path, 0, 1) !== '/') {
        // Base path must start with a leading '/'.
        $base_path = '/' . $base_path;
      }
      $input->setOption('views-page-path', $base_path);
    }

  }

  protected function setTemp() {
    $input = $this->getIo()->getInput();

    $input->setOption('module', 'edu_course');
    $input->setOption('entity-type', 'edu_course');
    $input->setOption('menu-root', 'admin');
    //    $input->setOption('menu-parent', 'menu-parent.mock');
    $input->setOption('views-page-path', '/admin/edu');
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {

    $this->setTemp();

    // --module option
    $this->getModuleOption();

    // --entity-type option
    $this->getEntityTypeOption();

    // --views-page-path option
    $this->getViewPathOption();


    // --menu-root
    $this->getMenuRootOption();
    // --menu-parent
    $this->getMenuParentOption();
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {


    $params = $input->getOptions();
    $params += $this->additionalParams($params);

    $this->getIo()->info($this->trans('commands.generate.views.messages.success'));
    $this->generator->generate($params);
  }

  protected function additionalParams($params) {
    /**
     * module_name
     * entity_type
     * views_page_path
     *
     * menu_name
     * menu_parent
     *
     */


    $entity_type_id = $params['entity-type'];


    $entityType = $this->entityTypeManager->getDefinition($entity_type_id);
    $entity_type_name = $entityType->getLabel();
    $entity = ['type' => $entity_type_id, 'name' => $entity_type_name];


    $bundles_info = Drupal::service('entity_type.bundle.info')->getBundleInfo($entity_type_id);

    $bundles = [];
    foreach ($bundles_info as $id => $bundle_info) {
      $bundles[] = ['id' => $id, 'title' => $bundle_info['label']];

    }

    return [
      'module_name' => $params['module_name'],
      'base_table' => $entity_type_id,
      'entity' => $entity,
      'menu' => [
        'title' => $entity_type_name,
        'url' => $params['views-pag-_path'],
        'root' => $params['menu-root'],
        'parent' => $params['menu-parent'],
      ],
      'views' => [
        'id' => "{$params['menu-root']}_{$entity_type_id}",
        'title' => "({$params['menu-root']}) {$entity_type_name}",
      ],
      'bundles' => $bundles,

    ];
  }

}
