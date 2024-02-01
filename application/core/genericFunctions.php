<?php 

// generic function for return eQsl path //
function wl_getPathEqsl() {
    $CI =& get_instance();
    $CI->load->model('Eqsl_images');
    return $CI->Eqsl_images->get_imagePath();
}

// generic function for return Qsl path //
function wl_getPathQsl() {
    $CI =& get_instance();
    $CI->load->model('Qsl_model');
    return $CI->Qsl_model->get_imagePath();
}

?>