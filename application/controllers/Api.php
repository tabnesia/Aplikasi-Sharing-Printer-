<?php
defined('BASEPATH') OR exit('No direct script access allowed');


class Api extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        header('Content-Type: application/json');

   
        $this->load->model('Printer_model');
        $this->load->model('Print_job_model');
    }

    private function json($data, $code = 200)
    {
        $this->output->set_status_header($code);
        echo json_encode($data);
    }


    public function pending_jobs($printer_id)
    {
        $printer = $this->Printer_model->get_by_id($printer_id);
        if (!$printer) {
            $this->json(['success' => false, 'message' => 'Printer not found'], 404);
            return;
        }

        $jobs = $this->Print_job_model->get_pending_for_printer($printer_id);

        $result = array_map(function ($job) {
            return [
                'id'            => (int) $job->id,
                'job_code'      => $job->job_code,
                'file_url'      => base_url($job->file_path),
                'original_name' => $job->original_name,
                'copies'        => (int) $job->copies,
                'paper_size'    => $job->paper_size,
                'color_mode'    => $job->color_mode,
            ];
        }, $jobs);

        $this->json(['success' => true, 'jobs' => $result]);
    }


    public function update_status($job_id)
    {
        $payload = json_decode($this->input->raw_input_stream, true);
        $status  = $payload['status'] ?? $this->input->post('status');
        $message = $payload['message'] ?? $this->input->post('message');

        $allowed = ['processing', 'printing', 'completed', 'failed'];
        if (!in_array($status, $allowed, true)) {
            $this->json(['success' => false, 'message' => 'Invalid status'], 422);
            return;
        }

        $this->Print_job_model->update_status($job_id, $status, $message);

        if ($status === 'failed' && $message) {
            $this->db->insert('print_agent_logs', [
                'job_id'  => $job_id,
                'level'   => 'error',
                'message' => $message,
            ]);
        }

        $this->json(['success' => true]);
    }

  
    public function heartbeat($printer_id)
    {
        $printer = $this->Printer_model->get_by_id($printer_id);
        if (!$printer) {
            $this->json(['success' => false, 'message' => 'Printer not found'], 404);
            return;
        }

        $this->Printer_model->heartbeat($printer_id, 'online');
        $this->json(['success' => true]);
    }
}