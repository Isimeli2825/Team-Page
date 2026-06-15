<?php

declare(strict_types=1);

namespace Drupal\team_page\Service;

/**
 * Interface for team member data services.
 *
 * Defines the contract for providing team member data to the Team Page.
 * This abstraction allows swapping data sources (hardcoded, entities, DB,
 * external API) without changing controllers or templates.
 *
 * Follows the Repository pattern from Domain-Driven Design, adapted for
 * Drupal's service container.
 */
interface TeamMemberServiceInterface {

  /**
   * Returns all team members.
   *
   * @return array
   *   An array of team member data arrays, each containing:
   *   - name: string (required)
   *   - position: string (required)
   *   - bio: string (optional)
   *   - image: string (URL, optional)
   *   - email: string (optional)
   *   - socials: array (key-value pairs, optional)
   *   - department: string (optional)
   *   - cta: array|null (optional, with 'text' and 'url' keys)
   *   - weight: int (optional, for ordering)
   */
  public function getTeamMembers(): array;

  /**
   * Returns unique department names across all team members.
   *
   * @return array
   *   An array of department name strings, sorted alphabetically.
   */
  public function getDepartments(): array;

  /**
   * Returns team members grouped by department.
   *
   * @return array
   *   An associative array keyed by department name, each containing:
   *   - department: string
   *   - members: array of member data arrays
   *   Executive Leadership department appears first if present.
   */
  public function getTeamMembersByDepartment(): array;

  /**
   * Returns a single team member by index.
   *
   * @param int $index
   *   The zero-based index of the team member.
   *
   * @return array|null
   *   The team member data array, or NULL if not found.
   */
  public function getTeamMember(int $index): ?array;

}
