<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap\Tests\XmlSitemapRebuildTest.
 */

namespace Drupal\xmlsitemap\Tests;

/**
 * Tests the rebuild process of sitemaps.
 */
class XmlSitemapRebuildTest extends XmlSitemapTestBase {

  public static $modules = array('xmlsitemap', 'path', 'node', 'system', 'user', 'help', 'block');
  protected $config;

  public static function getInfo() {
    return array(
      'name' => 'XML sitemap rebuild tests',
      'description' => 'Rebuild tests for the XML sitemap module.',
      'group' => 'XML sitemap',
    );
  }

  public function setUp() {
    parent::setUp();

    $this->config = \Drupal::configFactory()->get('xmlsitemap.settings');
    $this->admin_user = $this->drupalCreateUser(array('administer xmlsitemap', 'access administration pages', 'access site reports', 'administer users', 'administer permissions'));

    $this->drupalPlaceBlock('system_help_block', array('region' => 'help'));

    // allow anonymous user to view user profiles
    $user_role = entity_load('user_role', DRUPAL_ANONYMOUS_RID);
    $user_role->grantPermission('access user profiles');
    $user_role->save();
  }

  public function testSimpleRebuild() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet('admin/config/search/xmlsitemap/rebuild');
    $this->assertResponse(200);
    $this->assertText(t("This action rebuilds your site's XML sitemap and regenerates the cached files, and may be a lengthy process. If you just installed XML sitemap, this can be helpful to import all your site's content into the sitemap. Otherwise, this should only be used in emergencies."));

    $this->drupalPostForm(NULL, array(), t('Save configuration'));
    $this->assertText('The sitemap links were rebuilt.');
  }

  public function testUserLinksRebuild() {
    $this->config->set('xmlsitemap_entity_user', 1);
    $this->config->set('xmlsitemap_entity_user_bundle_user', 1);
    $this->config->save();
    xmlsitemap_link_bundle_settings_save('user', 'user', array('status' => 1, 'priority' => 0.4));

    $dummy_user = $this->drupalCreateUser(array());
    $this->drupalLogin($this->admin_user);
    $this->drupalPostForm('admin/config/search/xmlsitemap/rebuild', array(), t('Save configuration'));
    $this->assertText('The sitemap links were rebuilt.');
    $this->assertSitemapLinkValues('user', $dummy_user->id(), array('status' => 1, 'priority' => 0.4));
    $this->drupalGet('sitemap.xml');
    $this->assertRaw("user/{$dummy_user->id()}");
  }

}