<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Remove non-unique indexes that exactly duplicate Finance unique indexes. */
class Migration_Remove_duplicate_finance_indexes extends CI_Migration
{
    public function up()
    {
        $indexes = $this->db->query(
            'SELECT table_name AS finance_table, index_name AS finance_index, '
            . 'GROUP_CONCAT(column_name ORDER BY seq_in_index) AS finance_columns '
            . 'FROM information_schema.statistics '
            . 'WHERE table_schema = DATABASE() AND table_name REGEXP "^fin_" AND non_unique = 1 '
            . 'GROUP BY table_name, index_name'
        );
        if ($indexes === FALSE) {
            return;
        }

        foreach ($indexes->result() as $index) {
            $matching_unique = $this->db->query(
                'SELECT index_name FROM information_schema.statistics '
                . 'WHERE table_schema = DATABASE() AND table_name = ? AND non_unique = 0 AND index_name <> "PRIMARY" '
                . 'GROUP BY index_name HAVING GROUP_CONCAT(column_name ORDER BY seq_in_index) = ? LIMIT 1',
                array($index->finance_table, $index->finance_columns)
            );
            if ($matching_unique === FALSE || $matching_unique->num_rows() === 0) {
                continue;
            }

            $this->db->query(
                'ALTER TABLE ' . $this->db->escape_identifiers($index->finance_table)
                . ' DROP INDEX ' . $this->db->escape_identifiers($index->finance_index)
            );
        }
    }

    public function down()
    {
        // Duplicate non-unique indexes are intentionally not reintroduced.
    }
}
