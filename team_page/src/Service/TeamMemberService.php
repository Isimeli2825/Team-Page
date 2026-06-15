<?php

declare(strict_types=1);

namespace Drupal\team_page\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\image\Entity\ImageStyle;

/**
 * Service for managing team member data from Drupal entities.
 *
 * Fetches team members from 'team_member' content type nodes,
 * grouped by 'departments' taxonomy vocabulary.
 *
 * This is the dynamic version — team members are managed via the
 * Drupal Admin UI at /admin/content or /node/add/team_member.
 *
 * @see \Drupal\team_page\Service\TeamMemberServiceInterface
 */
class TeamMemberService implements TeamMemberServiceInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected FileUrlGeneratorInterface $fileUrlGenerator;

  /**
   * Constructs a TeamMemberService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    FileUrlGeneratorInterface $file_url_generator,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public function getTeamMembers(): array {
    $members = [];

    try {
      $nids = $this->entityTypeManager->getStorage('node')
        ->getQuery()
        ->condition('type', 'team_member')
        ->condition('status', 1)
        ->sort('field_weight', 'ASC')
        ->sort('title', 'ASC')
        ->accessCheck(TRUE)
        ->execute();

      if (empty($nids)) {
        return [];
      }

      $nodes = $this->entityTypeManager->getStorage('node')
        ->loadMultiple($nids);

      foreach ($nodes as $node) {
        $member = $this->buildMemberFromNode($node);
        if ($member !== NULL) {
          $members[] = $member;
        }
      }
    }
    catch (\Exception $e) {
      // If query fails (e.g. content type doesn't exist yet), return empty.
      return [];
    }

    return $members;
  }

  /**
   * Builds a team member data array from a node object.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The team member node.
   *
   * @return array|null
   *   The member data array, or NULL if invalid.
   */
  protected function buildMemberFromNode($node): ?array {
    if (!$node || $node->isNew()) {
      return NULL;
    }

    // Required fields.
    $name = $node->label();
    $position = $node->get('field_position')->value ?? '';

    if (empty($name) || empty($position)) {
      return NULL;
    }

    // Get profile image URL.
    $image_url = '';
    if (!$node->get('field_profile_image')->isEmpty()) {
      $file = $node->get('field_profile_image')->entity;
      if ($file) {
        // Use medium image style if available.
        $image_uri = $file->getFileUri();
        $image_url = $this->fileUrlGenerator->generateAbsoluteString($image_uri);
      }
    }

    // Get department.
    $department = '';
    if (!$node->get('field_department')->isEmpty()) {
      $term = $node->get('field_department')->entity;
      if ($term) {
        $department = $term->label();
      }
    }

    // Get social links.
    $socials = [];
    if (!$node->get('field_social_links')->isEmpty()) {
      foreach ($node->get('field_social_links') as $item) {
        $title = $item->title ?? '';
        $uri = $item->uri ?? '';
        if (!empty($uri)) {
          // Use the link title as the platform key (lowercase).
          $key = !empty($title) ? strtolower(str_replace(' ', '_', $title)) : 'link';
          $socials[$key] = $uri;
        }
      }
    }

    // Get CTA link.
    $cta = NULL;
    if (!$node->get('field_cta_link')->isEmpty()) {
      $cta_item = $node->get('field_cta_link')->first();
      if ($cta_item) {
        $cta = [
          'text' => $cta_item->title ?? 'Contact',
          'url' => $cta_item->uri ?? '#',
        ];
      }
    }

    return [
      'name' => $name,
      'position' => $position,
      'bio' => $node->get('field_bio')->value ?? '',
      'image' => $image_url,
      'email' => $node->get('field_email')->value ?? '',
      'socials' => $socials,
      'department' => $department,
      'cta' => $cta,
      'weight' => (int) ($node->get('field_weight')->value ?? 0),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDepartments(): array {
    $departments = [];

    try {
      $terms = $this->entityTypeManager->getStorage('taxonomy_term')
        ->loadByProperties(['vid' => 'departments']);

      // Sort: Executive Leadership first, then alphabetical.
      $exec_term = NULL;
      foreach ($terms as $term) {
        $name = $term->label();
        if ($name === 'Executive Leadership') {
          $exec_term = $name;
        }
        else {
          $departments[] = $name;
        }
      }

      sort($departments);

      if ($exec_term !== NULL) {
        array_unshift($departments, $exec_term);
      }
    }
    catch (\Exception $e) {
      return [];
    }

    return $departments;
  }

  /**
   * {@inheritdoc}
   */
  public function getTeamMembersByDepartment(): array {
    $members = $this->getTeamMembers();
    $teams = [];

    foreach ($members as $member) {
      $dept = !empty($member['department']) ? $member['department'] : 'Other';
      if (!isset($teams[$dept])) {
        $teams[$dept] = [
          'department' => $dept,
          'members' => [],
        ];
      }
      $teams[$dept]['members'][] = $member;
    }

    // Sort: Executive Leadership first, then alphabetical.
    $exec_team = NULL;
    if (isset($teams['Executive Leadership'])) {
      $exec_team = $teams['Executive Leadership'];
      unset($teams['Executive Leadership']);
    }

    ksort($teams);

    if ($exec_team !== NULL) {
      $teams = array_merge(['Executive Leadership' => $exec_team], $teams);
    }

    return $teams;
  }

  /**
   * {@inheritdoc}
   */
  public function getTeamMember(int $index): ?array {
    $members = $this->getTeamMembers();

    if (isset($members[$index])) {
      return $members[$index];
    }

    return NULL;
  }

}
