<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Admin extends Admin_Controller {

    function __construct() {
        parent::Admin_Controller();
        $this->load->model('suppliers_m');
        $this->load->module_model('categories', 'categories_m');
    }

    // Admin: List Suppliers
    function index() {
        // Create pagination links
    	$total_rows = $this->suppliers_m->countSuppliers();
    	$this->data->pagination = create_pagination('admin/suppliers/index', $total_rows);

    	// Using this data, get the relevant results
    	$this->data->suppliers = $this->suppliers_m->getSuppliers(array('limit' => $this->data->pagination['limit']));
    	
        $this->layout->create('admin/index', $this->data);
        return;
    }
    
    // Admin: Create new Supplier
    function create() {
        $this->load->library('validation');
        
        $rules['title'] = 'trim|required|max_length[40]|callback__check_title';
        $rules['description'] = 'trim|required';
        $rules['url'] = 'trim|required|prep_url|max_length[100]';
        $rules['category'] = 'required|callback__check_category';
        $this->validation->set_rules($rules);
        
        $fields['category'] = 'Category';
        $this->validation->set_fields($fields);
        
        if ($this->validation->run()) {

        	$data_array = $_POST;
			// ok, if we want to upload a logo
			if ($_FILES['userfile']['name'])
			{
				$upload_cfg['upload_path'] = APPPATH.'assets/img/suppliers';
	            $upload_cfg['allowed_types'] = 'gif|jpg|png';
	    		$upload_cfg['encrypt_name'] = true;
	            $this->load->library('upload', $upload_cfg);

				if($this->upload->do_upload())
				{
					$image = $this->upload->data();
	                if(!$this->_create_resize($image['file_name'], $this->settings->item('suppliers_width'), $this->settings->item('suppliers_height')))
					{
						$this->session->set_flashdata('error', $this->image_lib->display_errors('',''));
					}
					$data_array['image'] = $image['file_name'];
				} else {
					show_error($this->upload->display_errors());
				}
			}
			
			if($new_supplier_id = $this->suppliers_m->newSupplier($data_array))
			{
                $this->session->set_flashdata('success', 'The suppler "'.$this->input->post('title').'" has been added.');
			}
			
			else
			{
				$this->session->set_flashdata('error', 'Error occurred while trying to add new supplier.');
			}
			
			redirect('admin/suppliers/index');
        } 
        
		$this->data->categories = $this->categories_m->getCategories();
		$this->layout->create('admin/create', $this->data);
    }
    
    // Admin: Edit a Supplier
    function edit($slug = '') {  
		
		if (empty($slug)) redirect('admin/suppliers/index');
        
        $supplier = $this->suppliers_m->getSupplier($slug);
        if (!$supplier) redirect('admin/suppliers/index');
        
        $this->load->library('validation');
        $rules['description'] = 'trim|required';
        $rules['url'] = 'trim|required|prep_url|max_length[100]';
        $rules['category'] = 'required';
        $this->validation->set_rules($rules);
        $fields['category'] = 'Category';
        $this->validation->set_fields($fields);
        	
        foreach(array_keys($rules) as $field) {
        	if(isset($_POST[$field]))
        		$supplier->$field = $this->validation->$field;
        }
		
        if ($this->validation->run())
        {
			$data_array = $_POST;
			if ($_FILES['userfile']['name']) 
			{
				// delete olf file, is needed if new image extension is not the same as old ones
				$this->_delete_file(APPPATH.'assets/img/suppliers/', $supplier->image);
				$new_image_name = explode('.', $supplier->image);
				$upload_cfg['upload_path'] = APPATH.'assets/img/suppliers';
				$upload_cfg['overwrite'] = TRUE;
				$upload_cfg['new_name'] = $new_image_name[0];
				$upload_cfg['allowed_types'] = 'gif|jpg|png';
				$this->load->library('upload', $upload_cfg);
				
				if ($this->upload->do_upload()) 
				{
					$image = $this->upload->data();
					if(!$this->_create_resize($image['file_name'], $this->settings->item('suppliers_width'), $this->settings->item('suppliers_height')))
					{
						$this->session->set_flashdata('error', $this->image_lib->display_errors());
					}
					$data_array['image'] = $image['file_name'];
				} else {
					show_error($this->upload->display_errors());
				}
			}
			
			// update
			$this->suppliers_m->updateSupplier($supplier->id, $data_array);
			$this->session->set_flashdata('success', 'The suppler "'.$supplier->title.'" has been saved.');
			redirect('admin/suppliers/index');
        }

		$this->data->cur_categories = $this->suppliers_m->getCategoryies($supplier->id);
		$this->data->categories = $this->categories_m->getCategories();
		
		$this->data->supplier =& $supplier;
		
 		$this->layout->create('admin/edit', $this->data);
    }
    
    // Admin: Delete a Supplier
    function delete($id = 0) {
		$img_folder = APPPATH.'assets/img/suppliers/';
		
		// An ID was passed in the URL, lets delete that
		$id_array = ($id > 0) ? array($id) : array_keys($this->input->post('delete'));
		
		if(empty($id_array))
    	{
			$this->session->set_flashdata('error', 'You need to select suppliers to delete1.');
			redirect('admin/supplirs/index');
		}
		
		// Delete multiple
		$deleted = 0;
		$to_delete = 0;
		foreach ($id_array as $id)
		{
			if($supplier = $this->suppliers_m->getSupplier($id))
			{
				if($this->suppliers_m->deleteSupplier($supplier->id))
				{
					$deleted++;
				}

				else
				{
					$this->session->set_flashdata('error', 'Error occurred while trying to delete supplier "'.$supplier->title.'".');
				}
				
			}
			
			else
			{
				$this->session->set_flashdata('error', 'Supplier '.$id.' not found.');
			}
			
			$to_delete++;
		}
		
		if( $deleted > 0 )
		{
			$this->session->set_flashdata('success', $deleted.' suppliers out of '.$to_delete.' successfully deleted.');
		}
		
		redirect('admin/suppliers/index');
    }

    
    // Callback: From create
    function _check_title($title) {
        if ($this->suppliers_m->getSupplier(url_title($title))) {
            $this->validation->set_message('_check_title', 'A supplier with that title already exists.');
            return FALSE;
        } else {
            return TRUE;
        }
    }
    
    // Private: Create resize of Cropped Image to ensure it's a certain size
    function _create_resize($homeimg = '', $x, $y) {
        unset($img_cfg);
        $img_cfg['source_image'] = APPPATH.'assets/img/suppliers/' . $homeimg;
        $img_cfg['new_image'] = APPPATH.'assets/img/suppliers/' . $homeimg;
        $img_cfg['maintain_ratio'] = true;
        $img_cfg['width'] = $x;
        $img_cfg['height'] = $y;
        $this->load->library('image_lib');
        $this->image_lib->initialize($img_cfg);
		if ($this->image_lib->resize())
		{
		    return TRUE;
		} else {
			return FALSE;
		}
    }

}

?>