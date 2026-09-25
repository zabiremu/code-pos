<?php

namespace Tests\Unit;

use App\Enums\Role;
use PHPUnit\Framework\TestCase;

/** Pure unit test - no database, no framework boot. */
class RoleEnumTest extends TestCase
{
    public function test_values_returns_every_role_as_a_plain_string(): void
    {
        $this->assertSame(
            ['admin', 'manager', 'cashier', 'waiter', 'kitchen'],
            Role::values()
        );
    }

    public function test_every_case_has_a_human_readable_label(): void
    {
        foreach (Role::cases() as $role) {
            $this->assertNotSame('', trim($role->label()));
        }
    }

    public function test_from_a_stored_value_round_trips_to_the_same_case(): void
    {
        $this->assertSame(Role::Admin, Role::from('admin'));
        $this->assertSame(Role::Kitchen, Role::from('kitchen'));
    }
}
