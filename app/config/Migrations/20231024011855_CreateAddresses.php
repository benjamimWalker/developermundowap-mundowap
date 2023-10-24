<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateAddresses extends AbstractMigration
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
        $table = $this->table('addresses');
        $table
            ->addColumn('foreign_table', 'string', ['limit' => 100])
            ->addColumn('foreign_id', 'integer')
            ->addColumn('postal_code', 'string', ['limit' => 8])
            ->addColumn('state', 'string', ['limit' => 2])
            ->addColumn('city', 'string', ['limit' => 200])
            ->addColumn('sublocality', 'string', ['limit' => 200])
            ->addColumn('street', 'string', ['limit' => 200])
            ->addColumn('street_number', 'string', ['limit' => 200])
            ->addColumn('complement', 'string', ['limit' => 200, 'default' => ''])
            ->addPrimaryKey(['id'])
            ->addIndex(['foreign_table', 'foreign_id'], ['unique' => true])
            ->create();
    }
}
