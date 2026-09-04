<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Printer_model extends CI_Model
{
    protected $table = 'printers';

    public function __construct()
    {
        parent::__construct();
    }

    public function get_all($active_only = false)
    {
        if ($active_only) {
            $this->db->where('is_active', 1);
        }
        $this->db->order_by('name', 'ASC');
        return $this->db->get($this->table)->result();
    }

    public function get_by_id($id)
    {
        $this->db->where('id', $id);
        return $this->db->get($this->table)->row();
    }

    public function create(array $data)
    {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function update($id, array $data)
    {
        $this->db->where('id', $id);
        return $this->db->update($this->table, $data);
    }


    public function heartbeat($id, $status = 'online')
    {
        return $this->update($id, [
            'status' => $status,
            'last_seen_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function delete($id)
    {
        $this->db->where('id', $id);
        return $this->db->delete($this->table);
    }
}
