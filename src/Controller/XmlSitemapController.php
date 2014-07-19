<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap\Controller\XmlSitemapController.
 */

namespace Drupal\xmlsitemap\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class XmlSitemapController extends ControllerBase {

  public function renderSitemapXml() {
    $sitemap = xmlsitemap_sitemap_load_by_context();
    if (!$sitemap) {
      throw new NotFoundHttpException();
    }
    $chunk = xmlsitemap_get_current_chunk($sitemap);
    $file = xmlsitemap_sitemap_get_file($sitemap, $chunk);

    // Provide debugging information if enabled.
    if (\Drupal::state()->get('xmlsitemap_developer_mode')) {
      $output = array();
      $context = xmlsitemap_get_current_context();
      $output[] = "Current context: " . print_r($context, TRUE);
      $output[] = "Sitemap: " . print_r($sitemap, TRUE);
      $output[] = "Chunk: $chunk";
      $output[] = "Cache file location: $file";
      $output[] = "Cache file exists: " . (file_exists($file) ? 'Yes' : 'No');
      return new Response(implode('<br />', $output));
    }
    xmlsitemap_output_file($file);
  }

  public function renderSitemapXsl() {
    // Read the XSL content from the file.
    $module_path = drupal_get_path('module', 'xmlsitemap');
    $xsl_content = file_get_contents($module_path . '/xsl/xmlsitemap.xsl');

    // Make sure the strings in the XSL content are translated properly.
    $replacements = array(
      'Sitemap file' => t('Sitemap file'),
      'Generated by the <a href="http://drupal.org/project/xmlsitemap">Drupal XML sitemap module</a>.' => t('Generated by the <a href="@link-xmlsitemap">Drupal XML sitemap module</a>.', array('@link-xmlsitemap' => 'http://drupal.org/project/xmlsitemap')),
      'Number of sitemaps in this index' => t('Number of sitemaps in this index'),
      'Click on the table headers to change sorting.' => t('Click on the table headers to change sorting.'),
      'Sitemap URL' => t('Sitemap URL'),
      'Last modification date' => t('Last modification date'),
      'Number of URLs in this sitemap' => t('Number of URLs in this sitemap'),
      'URL location' => t('URL location'),
      'Change frequency' => t('Change frequency'),
      'Priority' => t('Priority'),
      '[jquery]' => base_path() . 'misc/jquery.js',
      '[jquery-tablesort]' => base_path() . $module_path . '/xsl/jquery.tablesorter.min.js',
      '[xsl-js]' => base_path() . $module_path . '/xsl/xmlsitemap.xsl.js',
      '[xsl-css]' => base_path() . $module_path . '/xsl/xmlsitemap.xsl.css',
    );
    $xsl_content = strtr($xsl_content, $replacements);

    // Output the XSL content.
    drupal_add_http_header('Content-type', 'application/xml; charset=utf-8');
    drupal_add_http_header('X-Robots-Tag', 'noindex, follow');
    return new Response($xsl_content);
  }

}
