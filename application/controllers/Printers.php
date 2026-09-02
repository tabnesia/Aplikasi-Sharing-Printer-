<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Printers extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $data['title']    = 'Printer';
        $data['printers'] = $this->Printer_model->get_all();

        $this->load->view('layout/header', $data);
        $this->load->view('printers/index', $data);
        $this->load->view('layout/footer');
    }

    public function create()
    {
        $data['title']   = 'Tambah Printer';
        $data['printer'] = null;

        $this->load->view('layout/header', $data);
        $this->load->view('printers/form', $data);
        $this->load->view('layout/footer');
    }

    public function store()
    {
        $this->form_validation->set_rules('name', 'Nama printer', 'required|trim|max_length[100]');
        $this->form_validation->set_rules('driver', 'Tipe koneksi', 'required|in_list[raw,windows]');

        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('printers/create');
            return;
        }

        $this->Printer_model->create($this->_collect_input());
        $this->session->set_flashdata('success', 'Printer berhasil ditambahkan.');
        redirect('printers');
    }

    public function edit($id)
    {
        $printer = $this->Printer_model->get_by_id($id);
        if (!$printer) {
            show_404();
        }

        $data['title']   = 'Edit Printer';
        $data['printer'] = $printer;

        $this->load->view('layout/header', $data);
        $this->load->view('printers/form', $data);
        $this->load->view('layout/footer');
    }

    public function update($id)
    {
        if (!$this->Printer_model->get_by_id($id)) {
            show_404();
        }

        $this->form_validation->set_rules('name', 'Nama printer', 'required|trim|max_length[100]');
        $this->form_validation->set_rules('driver', 'Tipe koneksi', 'required|in_list[raw,windows]');

        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('printers/' . $id . '/edit');
            return;
        }

        $this->Printer_model->update($id, $this->_collect_input());
        $this->session->set_flashdata('success', 'Printer berhasil diperbarui.');
        redirect('printers');
    }

    public function delete($id)
    {
        $this->Printer_model->delete($id);
        $this->session->set_flashdata('success', 'Printer dihapus.');
        redirect('printers');
    }

    private function _collect_input()
    {
        $driver = $this->input->post('driver');

        return [
            'name'                  => $this->input->post('name'),
            'location'              => $this->input->post('location'),
            'driver'                => $driver,
            'ip_address'            => $driver === 'raw' ? $this->input->post('ip_address') : null,
            'port'                  => $driver === 'raw' ? ((int) $this->input->post('port') ?: 9100) : 9100,
            'windows_printer_name'  => $driver === 'windows' ? $this->input->post('windows_printer_name') : null,
            'is_active'             => $this->input->post('is_active') ? 1 : 0,
        ];
    }
}
