<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateStores extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('stores');
        $table
            ->addColumn('name', 'string', ['limit' => 200])
            ->addPrimaryKey(['id'])
            ->create();
    }
}
