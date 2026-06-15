<?php

declare(strict_types=1);

namespace Drupal\team_page\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\team_page\Service\TeamMemberServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the Team Page (/team).
 *
 * Returns a render array using Drupal's best practices:
 * - Uses the 'team_page' theme hook defined in hook_theme()
 * - Attaches CSS/JS libraries via #attached
 * - Sets cache metadata for Drupal's cache system
 * - Follows the Render API patterns for maximum compatibility
 *   with Layout Builder and other Drupal subsystems.
 *
 * Dependency injection is used to obtain the team member data service,
 * making the controller testable and allowing the data source to be
 * swapped without changing controller code.
 */
class TeamPageController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The team member service.
   *
   * @var \Drupal\team_page\Service\TeamMemberServiceInterface
   */
  protected TeamMemberServiceInterface $teamMemberService;

  /**
   * Constructs a new TeamPageController instance.
   *
   * @param \Drupal\team_page\Service\TeamMemberServiceInterface $team_member_service
   *   The team member data service.
   */
  public function __construct(TeamMemberServiceInterface $team_member_service) {
    $this->teamMemberService = $team_member_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('team_page.team_member_service')
    );
  }

  /**
   * Builds the team page render array.
   *
   * This method constructs a complete render array that Drupal will
   * render into HTML. Key architectural decisions:
   *
   * 1. **Theme hook**: Uses the custom 'team_page' theme hook registered
   *    via hook_theme(), which maps to team-page.html.twig.
   *
   * 2. **Library attachment**: CSS and JS are attached via the '#attached'
   *    render API property, ensuring they are included only on this page.
   *
   * 3. **Cache metadata**: Cache tags and contexts are set so Drupal's
   *    caching system knows when to invalidate and how to vary the cache.
   *    This is critical for production performance.
   *
   * 4. **Data flow**: Service → Controller → Preprocessor → Twig.
   *    No business logic in templates.
   *
   * @return array
   *   A render array for the team page, ready for Drupal's rendering system.
   */
  public function build(): array {
    $members = $this->teamMemberService->getTeamMembers();
    $departments = $this->teamMemberService->getDepartments();
    $teams = $this->teamMemberService->getTeamMembersByDepartment();

    $build = [
      '#theme' => 'team_page',
      '#page_title' => $this->t('Our Team'),
      '#page_description' => $this->t('Meet the dedicated professionals driving educational assessment excellence across the Pacific.'),
      '#members' => $members,
      '#departments' => $departments,
      '#active_department' => NULL,
      '#teams' => $teams,
      '#show_filters' => TRUE,
      '#show_modals' => TRUE,
      '#grid_columns' => 3,

      // Attach the CSS and JS libraries.
      // The #attached property is the Drupal-standard way to include
      // assets on a per-page basis. This is compatible with:
      //   - Standard page rendering
      //   - Layout Builder
      //   - BigPipe
      //   - AJAX requests
      '#attached' => [
        'library' => [
          'team_page/team-page',
        ],
      ],

      // Cache metadata ensures the page is cached efficiently.
      // Cache tags allow the page to be invalidated when team data changes
      // (e.g. when a team_member node is created, updated, or deleted).
      // Cache contexts allow the cache to vary by URL and department filter.
      '#cache' => [
        'tags' => [
          'team_page:members',
          'node_list:team_member',
          'taxonomy_term_list:departments',
        ],
        'contexts' => [
          'url.path',
          'url.query_args:department',
        ],
        // Maximum cache time: 1 hour.
        // Can be increased in production if data doesn't change frequently.
        'max-age' => 3600,
      ],
    ];

    return $build;
  }

}
