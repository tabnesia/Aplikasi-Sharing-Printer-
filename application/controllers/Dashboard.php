<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Print_job_model');
        $this->load->model('Printer_model');
    }

    public function index()
    {
        $data['title']        = 'Dashboard';
        $data['counts']       = $this->Print_job_model->counts_by_status();
        $data['printers']     = $this->Printer_model->get_all();
        $data['recent_jobs']  = array_slice($this->Print_job_model->get_all(), 0, 8);

        // Aktivitas cetak hari ini (per jam)
        $data['hourly']       = $this->Print_job_model->get_hourly_activity_today();
        $data['today_summary']= $this->Print_job_model->get_today_success_failed();

        $this->load->view('layout/header', $data);
        $this->load->view('dashboard/index', $data);
        $this->load->view('layout/footer');
    }
}