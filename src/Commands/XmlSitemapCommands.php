<?php

namespace Drupal\xmlsitemap\Commands;

use Drupal\Component\Utility\Timer;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class XmlSitemapCommands extends DrushCommands {

  /**
   * The config.factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The module_handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Default database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * XmlSitemapCommands constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config.factory service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module_handler service.
   * @param \Drupal\Core\Database\Connection $connection
   *   Default database connection.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    ModuleHandlerInterface $moduleHandler,
    Connection $connection
  ) {
    parent::__construct();
    $this->config = $configFactory->get('xmlsitemap.settings');
    $this->moduleHandler = $moduleHandler;
    $this->connection = $connection;
  }

  /**
   * Regenerate the XML sitemap files.
   *
   * @validate-module-enabled xmlsitemap
   *
   * @command xmlsitemap:regenerate
   * @aliases xmlsitemap-regenerate
   */
  public function regenerate() {
    // Run the batch process.
    Timer::start('xmlsitemap_regenerate');
    xmlsitemap_run_unprogressive_batch('xmlsitemap_regenerate_batch');

    $vars = [
      '@timer' => Timer::read('xmlsitemap_regenerate'),
      '@memory-peak' => format_size(memory_get_peak_usage(TRUE)),
    ];
    $this->output()->writeln(dt('XML sitemap files regenerated in @timer ms. Peak memory usage: @memory-peak.', $vars));
    Timer::stop('xmlsitemap_regenerate');
  }

  /**
   * Dump and re-process all possible XML sitemap data, then regenerate files.
   *
   * @validate-module-enabled xmlsitemap
   *
   * @command xmlsitemap:rebuild
   * @aliases xmlsitemap-rebuild
   */
  public function rebuild() {
    // Build a list of rebuildable link types.
    $rebuild_types = xmlsitemap_get_rebuildable_link_types();
    if (empty($rebuild_types)) {
      throw new \Exception("No link types are rebuildable.");
    }

    // Run the batch process.
    Timer::start('xmlsitemap_rebuild');
    xmlsitemap_run_unprogressive_batch('xmlsitemap_rebuild_batch', $rebuild_types, TRUE);

    $vars = [
      '@timer' => Timer::read('xmlsitemap_rebuild'),
      '@memory-peak' => format_size(memory_get_peak_usage(TRUE)),
    ];
    $this->output()->writeln(dt('XML sitemap files rebuilt in @timer ms. Peak memory usage: @memory-peak.', $vars));
    Timer::stop('xmlsitemap_rebuild');
  }

  /**
   * Process un-indexed XML sitemap links.
   *
   * @param array $options
   *   An associative array of options obtained from cli, aliases, config, etc.
   *
   * @option limit
   *   The limit of links of each type to process.
   * @validate-module-enabled xmlsitemap
   *
   * @command xmlsitemap:index
   * @aliases xmlsitemap-index
   */
  public function index(array $options = ['limit' => NULL]) {
    $limit = (int) ($options['limit'] ?: $this->config->get('batch_limit'));
    $count_before = $this->connection->select('xmlsitemap', 'x')->countQuery()->execute()->fetchField();

    $this->moduleHandler->invokeAll('xmlsitemap_index_links', ['limit' => $limit]);

    $count_after = $this->connection->select('xmlsitemap', 'x')->countQuery()->execute()->fetchField();

    if ($count_after == $count_before) {
      $this->output()->writeln(dt('No new XML sitemap links to index.'));
    }
    else {
      $this->output()->writeln(dt('Indexed @count new XML sitemap links.', ['@count' => $count_after - $count_before]));
    }
  }

}
