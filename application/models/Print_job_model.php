<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Print_job_model extends CI_Model
{
    protected $table = 'print_jobs';

    public function __construct()
    {
        parent::__construct();
    }

    public function generate_job_code()
    {
        $prefix = 'PJ-' . date('Ymd') . '-';
        $this->db->select('job_code');
        $this->db->like('job_code', $prefix, 'after');
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);
        $last = $this->db->get($this->table)->row();

        $next = 1;
        if ($last) {
            $lastNumber = (int) substr($last->job_code, -4);
            $next = $lastNumber + 1;
        }
        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    public function create(array $data)
    {
        $data['job_code'] = $this->generate_job_code();
        $data['status'] = 'pending';
        $data['submitted_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function get_all($filters = [])
    {
        $this->db->select('print_jobs.*, printers.name AS printer_name');
        $this->db->from($this->table);
        $this->db->join('printers', 'printers.id = print_jobs.printer_id', 'left');

        if (!empty($filters['status'])) {
            $this->db->where('print_jobs.status', $filters['status']);
        }
        if (!empty($filters['printer_id'])) {
            $this->db->where('print_jobs.printer_id', $filters['printer_id']);
        }

        $this->db->order_by('print_jobs.id', 'DESC');
        return $this->db->get()->result();
    }

    public function get_by_id($id)
    {
        $this->db->select('print_jobs.*, printers.name AS printer_name, printers.ip_address, printers.port');
        $this->db->from($this->table);
        $this->db->join('printers', 'printers.id = print_jobs.printer_id', 'left');
        $this->db->where('print_jobs.id', $id);
        return $this->db->get()->row();
    }


    public function get_pending_for_printer($printer_id)
    {
        $this->db->where('printer_id', $printer_id);
        $this->db->where('status', 'pending');
        $this->db->order_by('id', 'ASC');
        return $this->db->get($this->table)->result();
    }

    public function update_status($id, $status, $error_message = null)
    {
        $data = ['status' => $status];

        if ($status === 'processing') {
            $data['processed_at'] = date('Y-m-d H:i:s');
        } elseif (in_array($status, ['completed', 'failed', 'cancelled'])) {
            $data['completed_at'] = date('Y-m-d H:i:s');
        }
        if ($error_message !== null) {
            $data['error_message'] = $error_message;
        }

        $this->db->where('id', $id);
        return $this->db->update($this->table, $data);
    }

    public function cancel($id)
    {
        return $this->update_status($id, 'cancelled');
    }

    public function counts_by_status()
    {
        $this->db->select('status, COUNT(*) as total');
        $this->db->group_by('status');
        $rows = $this->db->get($this->table)->result();

        $counts = ['pending' => 0, 'processing' => 0, 'printing' => 0, 'completed' => 0, 'failed' => 0, 'cancelled' => 0];
        foreach ($rows as $row) {
            $counts[$row->status] = (int) $row->total;
        }
        return $counts;
    }

public function get_hourly_activity_today()
{
    $today = date('Y-m-d');

    $this->db->select("
        HOUR(submitted_at) AS hour,
        COUNT(*)           AS job_count,
        COALESCE(SUM(pages), 0) AS page_count
    ", false);
    $this->db->from('print_jobs');
    $this->db->where('DATE(submitted_at)', $today);
    $this->db->group_by('HOUR(submitted_at)');
    $this->db->order_by('hour', 'ASC');

    return $this->db->get()->result();
}

public function get_today_success_failed()
{
    $today = date('Y-m-d');

    $this->db->select("
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS success,
        SUM(CASE WHEN status = 'failed'    THEN 1 ELSE 0 END) AS failed
    ", false);
    $this->db->from('print_jobs');
    $this->db->where('DATE(submitted_at)', $today);

    $row = $this->db->get()->row();

    if (!$row) {
        return (object) ['success' => 0, 'failed' => 0];
    }
    $row->success = (int) $row->success;
    $row->failed  = (int) $row->failed;
    return $row;
}
}
