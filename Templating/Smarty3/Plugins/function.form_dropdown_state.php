<?php
function smarty_function_form_dropdown_state($params, $template)
{
    $defaults = array(
        'id' => 'State',
        'name' => 'State',
        'method' => null,
        'class' => '',
        'value' => null,
        'format' => 'name'
    );
    
    $params = array_merge($defaults, $params);
    
    $fieldName = $params['name'];
    
    switch (strtoupper($params['method']))
    {
        case 'GET':  $selectedSource = $_GET; break;
        case 'POST': $selectedSource = $_POST; break;
        default:     $selectedSource = $_REQUEST; break;
    }
    
    $selected = (isset($selectedSource[$fieldName])) ? $selectedSource[$fieldName] : null;
    
    $states = form_dropdown_state_getStatesArray();
    
    $dropdownHtml = '<select id="'.$params['id'].'" name="'.$params['name'].'" class="'.$params['class'].'">';
    foreach ($states as $state)
    {
        $option = sprintf('<option value="%1$s">%2$s</option>', $state['iso_2'], $state[$params['format']]);
        
        if ($state['iso_2'] == $selected)
        {
            $option = str_replace('<option', '<option selected="selected"', $option);
        }
        $dropdownHtml .= $option;
    }
    $dropdownHtml .= '</select>';
    
    return $dropdownHtml;
}

function form_dropdown_state_getStatesArray()
{
    static $states = array(
        array('iso_2'=>'AL', 'name' => 'Alabama'),
        array('iso_2'=>'AK', 'name' => 'Alaska'),
        array('iso_2'=>'AZ', 'name' => 'Arizona'),
        array('iso_2'=>'AR', 'name' => 'Arkansas'),
        array('iso_2'=>'CA', 'name' => 'California'),
        array('iso_2'=>'CO', 'name' => 'Colorado'),
        array('iso_2'=>'CT', 'name' => 'Connecticut'),
        array('iso_2'=>'DE', 'name' => 'Delaware'),
        array('iso_2'=>'DC', 'name' => 'District of Columbia'),
        array('iso_2'=>'FL', 'name' => 'Florida'),
        array('iso_2'=>'GA', 'name' => 'Georgia'),
        array('iso_2'=>'HI', 'name' => 'Hawaii'),
        array('iso_2'=>'ID', 'name' => 'Idaho'),
        array('iso_2'=>'IL', 'name' => 'Illinois'),
        array('iso_2'=>'IN', 'name' => 'Indiana'),
        array('iso_2'=>'IA', 'name' => 'Iowa'),
        array('iso_2'=>'KS', 'name' => 'Kansas'),
        array('iso_2'=>'KY', 'name' => 'Kentucky'),
        array('iso_2'=>'LA', 'name' => 'Louisiana'),
        array('iso_2'=>'ME', 'name' => 'Maine'),
        array('iso_2'=>'MD', 'name' => 'Maryland'),
        array('iso_2'=>'MA', 'name' => 'Massachusetts'),
        array('iso_2'=>'MI', 'name' => 'Michigan'),
        array('iso_2'=>'MN', 'name' => 'Minnesota'),
        array('iso_2'=>'MS', 'name' => 'Mississippi'),
        array('iso_2'=>'MO', 'name' => 'Missouri'),
        array('iso_2'=>'MT', 'name' => 'Montana'),
        array('iso_2'=>'NE', 'name' => 'Nebraska'),
        array('iso_2'=>'NV', 'name' => 'Nevada'),
        array('iso_2'=>'NH', 'name' => 'New Hampshire'),
        array('iso_2'=>'NJ', 'name' => 'New Jersey'),
        array('iso_2'=>'NM', 'name' => 'New Mexico'),
        array('iso_2'=>'NY', 'name' => 'New York'),
        array('iso_2'=>'NC', 'name' => 'North Carolina'),
        array('iso_2'=>'ND', 'name' => 'North Dakota'),
        array('iso_2'=>'OH', 'name' => 'Ohio'),
        array('iso_2'=>'OK', 'name' => 'Oklahoma'),
        array('iso_2'=>'OR', 'name' => 'Oregon'),
        array('iso_2'=>'PA', 'name' => 'Pennsylvania'),
        array('iso_2'=>'RI', 'name' => 'Rhode Island'),
        array('iso_2'=>'SC', 'name' => 'South Carolina'),
        array('iso_2'=>'SD', 'name' => 'South Dakota'),
        array('iso_2'=>'TN', 'name' => 'Tennessee'),
        array('iso_2'=>'TX', 'name' => 'Texas'),
        array('iso_2'=>'UT', 'name' => 'Utah'),
        array('iso_2'=>'VT', 'name' => 'Vermont'),
        array('iso_2'=>'VA', 'name' => 'Virginia'),
        array('iso_2'=>'WA', 'name' => 'Washington'),
        array('iso_2'=>'WV', 'name' => 'West Virginia'),
        array('iso_2'=>'WI', 'name' => 'Wisconsin'),
        array('iso_2'=>'WY', 'name' => 'Wyoming')
    );
    
    return $states;
}

?>