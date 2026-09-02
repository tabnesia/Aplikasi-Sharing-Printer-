<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Print_jobs extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $filters = [
            'status'     => $this->input->get('status'),
            'printer_id' => $this->input->get('printer_id'),
        ];

        $data['title']    = 'Print Jobs';
        $data['jobs']     = $this->Print_job_model->get_all($filters);
        $data['printers'] = $this->Printer_model->get_all();
        $data['filters']  = $filters;

        $this->load->view('layout/header', $data);
        $this->load->view('print_jobs/index', $data);
        $this->load->view('layout/footer');
    }

    public function create()
    {
        $data['title']    = 'New Print Job';
        $data['printers'] = $this->Printer_model->get_all(true);

        $this->load->view('layout/header', $data);
        $this->load->view('print_jobs/create', $data);
        $this->load->view('layout/footer');
    }

    public function store()
    {
        $this->form_validation->set_rules('printer_id', 'Printer', 'required|numeric');
        $this->form_validation->set_rules('user_name', 'Nama pengirim', 'required|trim|max_length[100]');
        $this->form_validation->set_rules('copies', 'Jumlah salinan', 'required|numeric|greater_than[0]');

        if ($this->form_validation->run() === FALSE || empty($_FILES['document']['name'])) {
            $this->session->set_flashdata('error', 'Lengkapi semua field dan pilih file yang akan dicetak.');
            redirect('print-jobs/create');
            return;
        }

        $upload_path = FCPATH . 'uploads/';
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0755, true);
        }

        $config['upload_path']   = $upload_path;
        $config['allowed_types'] = 'pdf|doc|docx|xls|xlsx|jpg|jpeg|png';
        $config['max_size']      = 20480; // 20MB
        $config['encrypt_name']  = TRUE;

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('document')) {
            $this->session->set_flashdata('error', $this->upload->display_errors('', ''));
            redirect('print-jobs/create');
            return;
        }

        $file = $this->upload->data();

        $job_id = $this->Print_job_model->create([
            'printer_id'    => (int) $this->input->post('printer_id'),
            'user_name'     => $this->input->post('user_name'),
            'original_name' => $file['client_name'],
            'file_name'     => $file['file_name'],
            'file_path'     => 'uploads/' . $file['file_name'],
            'file_type'     => $file['file_ext'] ? ltrim($file['file_ext'], '.') : null,
            'pages'         => (int) $this->input->post('pages') ?: 1,
            'copies'        => (int) $this->input->post('copies') ?: 1,
            'paper_size'    => $this->input->post('paper_size') ?: 'A4',
            'color_mode'    => $this->input->post('color_mode') ?: 'grayscale',
        ]);

        $this->session->set_flashdata('success', 'Print job berhasil dikirim ke antrian (ID #' . $job_id . ').');
        redirect('print-jobs');
    }

    public function view($id)
    {
        $job = $this->Print_job_model->get_by_id($id);
        if (!$job) {
            show_404();
        }

        $data['title'] = 'Detail Print Job';
        $data['job']   = $job;

        $this->load->view('layout/header', $data);
        $this->load->view('print_jobs/view', $data);
        $this->load->view('layout/footer');
    }

    public function cancel($id)
    {
        $this->Print_job_model->cancel($id);
        $this->session->set_flashdata('success', 'Print job #' . $id . ' dibatalkan.');
        redirect('print-jobs');
    }
}
