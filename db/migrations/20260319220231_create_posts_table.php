<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePostsTable extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        $table = $this->table("posts");
        $table
            ->addColumn("content", "string", ["limit" => 255, "null" => false])
            ->addColumn("delete_key", "string", [
                "limit" => 255,
                "null" => false,
            ])
            ->addColumn("board", "string", ["null" => false, "limit" => 64])
            ->addTimestamps("created_at", "updated_at")
            ->create();
    }
}
