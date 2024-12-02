<?php
function generate_template_data_fieldset( $sectionId, $sectionTitle, $fieldData ) {

	$templateData = `
  <fieldset class="wizard-form-section" id="' . $sectionId . '">
  <h3 class="wizard-form-section-title">' . $sectionTitle . '</h3>
  <div class="wizard-form-section-content">
    `;
	$templateData .= generate_wizard_fieldgroup( $fieldData );

	$templateData .= `
  </div>
  </fieldset>
  `;
	return $templateData;
}
function generate_wizard_fieldgroup( $fieldData, $namePrefix = '' ) {
	$template = '';

	foreach ( $fieldData as $field ) {
		$fieldType = $field['type'] ?? 'text';
		$fieldLabel = $field['label'] ?? '';
		$fieldValue = $field['value'] ?? '';
		$placeholder = $field['placeholder'] ?? '';
    $disabled = isset($field['disabled']) && $field['disabled'] === true ? 'disabled' : '';

		switch ( $fieldType ) {
			case 'text':
				$template .= '
      <div class="wizard-form-fieldgroup text" id="template-' . $field['id'] . '">
        <label class="wizard-form-fieldgroup-label">' . $fieldLabel . '</label>
        <div class="wizard-form-fieldgroup-value">
          <input type="text" class="wizard-form-fieldgroup-input" 
          name="' . $namePrefix . '[' . $field['id'] . ']" 
          value="' . $fieldValue . '"
          placeholder="' . $placeholder . '" '.$disabled.'>
        </div>
      </div>
    ';
				break;
			case 'textarea':
				$template .= '
        <div class="wizard-form-fieldgroup textarea" id="template-' . $field['id'] . '">
          <label class="wizard-form-fieldgroup-label">' . $fieldLabel . '</label>
          <div class="wizard-form-fieldgroup-value">
            <textarea class="wizard-form-fieldgroup-input" 
            name="' . $namePrefix . '[' . $field['id'] . ']" placeholder="'.$placeholder.'" ' . $disabled . '>' . $fieldValue . '</textarea>
          </div>
        </div>
      ';
				break;
			case 'select':
				$options = $field['options'] ?? [];
				$template .= '
          <div class="wizard-form-fieldgroup select" id="template-' . $field['id'] . '">
            <label class="wizard-form-fieldgroup-label">' . $fieldLabel . '</label>
            <div class="wizard-form-fieldgroup-value">
            <select class="wizard-form-fieldgroup-input" name="' . $namePrefix . '[' . $field['id'] . ']" ' . $disabled . '>
      ';
				foreach ( $options as $option ) {
					$selected = $option['value'] == $fieldValue ? 'selected' : '';
					$template .= '<option value="' . $option['value'] . '" ' . $selected . '>' . $option['label'] . '</option>';
				}
				$template .= '
            </select>
            </div>
          </div>
        ';
				break;
			case 'checkbox':
				$checked = $fieldValue ? 'checked' : '';
				$template .= '
          <div class="wizard-form-fieldgroup checkbox" id="template-' . $field['id'] . '">
            <label class="wizard-form-fieldgroup-label">
              ' . $fieldLabel . '
            </label>
            <div class="wizard-form-fieldgroup-value">
              <input type="checkbox" class="wizard-form-fieldgroup-value" name="' . $namePrefix . '[' . $field['id'] . ']" ' . $checked . ' ' . $disabled . '>
            </div>
          </div>
        ';
				break;

			case 'radio':
				$options = $field['options'] ?? [];
				$template .= '
          <div class="wizard-form-fieldgroup radio" id="template-' . $field['id'] . '">
            <label class="wizard-form-fieldgroup-label">' . $fieldLabel . '</label>
            <div class="wizard-form-fieldgroup-value">
            <div class="wizard-form-fieldgroup-input">
      ';
				foreach ( $options as $option ) {
					$checked = $option['value'] == $fieldValue ? 'checked' : '';
					$template .= '
              <label>
                <input type="radio" name="' . $namePrefix . '[' . $field['id'] . ']" value="' . $option['value'] . '" ' . $checked . ' ' . $disabled . '>
                ' . $option['label'] . '
              </label>
        ';
				}
				$template .= '
            </div>
            </div>
          </div>
        ';
				break;

			case 'checkbox-group':
				$options = $field['options'] ?? [];
				$template .= '
          <div class="wizard-form-fieldgroup checkbox-group" id="template-' . $field['id'] . '">
            <label class="wizard-form-fieldgroup-label">' . $fieldLabel . '</label>
            <div class="wizard-form-fieldgroup-value">
        ';
				foreach ( $options as $option ) {
					$checked = in_array( $option['value'], $field['value'] ) ? 'checked' : '';
					$template .= '
              <label>
                <input type="checkbox" name="' . $namePrefix . '[' . $field['id'] . '][]" value="' . $option['value'] . '" ' . $checked . ' ' . $disabled . '>
                ' . $option['label'] . '
              </label>
        ';
				}
				$template .= '
            </div>
          </div>
        ';
				break;

			case 'radio-group':
				$options = $field['options'] ?? [];
				$template .= '
          <div class="wizard-form-fieldgroup radio-group" id="template-' . $field['id'] . '">
            <label class="wizard-form-fieldgroup-label">' . $fieldLabel . '</label>
            <div class="wizard-form-fieldgroup-value">
        ';
				foreach ( $options as $option ) {
					$checked = $option['value'] == $field['value'] ? 'checked' : '';
					$template .= '
              <label>
                <input type="radio" name="' . $namePrefix . '[' . $field['id'] . ']" value="' . $option['value'] . '" ' . $checked . ' ' . $disabled . '>
                ' . $option['label'] . '
              </label>
        ';
				}
				$template .= '
            </div>
          </div>
        ';
				break;
			case 'repeater':
				$fields = $field['fields'] ?? [];
        $display = $field['display'] ?? '';
				$template .= '
          <div class="wizard-form-fieldgroup repeater" id="template-' . $field['id'] . '">
            <label class="wizard-form-fieldgroup-label">' . $fieldLabel . '</label>
            <div class="wizard-form-fieldgroup-value">
              <div class="repeater-rows">
        ';

				$rowValues = $field['value'] ?? []; // Retrieve the existing row values

				if ( empty( $rowValues ) ) {
					// Generate a placeholder message when there are no rows
					$template .= '<div class="empty-repeater-message"><em>Click "Add Row" to enter data</em></div>';
				} else {
					// Generate the markup for each existing row
					foreach ( $rowValues as $rowIndex => $rowValue ) {
						$template .= '
              <div class="repeater-row ' . $display . '">
            ';
						$template .= generate_wizard_fieldgroup( $fields, $field['id'] . '[' . $rowIndex . ']', $rowValue );
						$template .= '
                <a href="#" class="remove-row"><i class="fa-solid fa-xmark"></i></a>
              </div>
            ';
					}
				}

				// Generate the repeater row template, hidden with CSS
				$template .= '
          <div class="repeater-row-template ' . $display . '">
        ';
				$template .= generate_wizard_fieldgroup( $fields, $field['id'] . '[{{row}}]' );
				$template .= '
            <a href="#" class="remove-row"><i class="fa-solid fa-xmark"></i></a>
          </div>
        ';

				$template .= '
              </div>
              <a href="#" class="add-row">Add row</a>
            </div>
          </div>
        ';
				break;
		}
	}

	return $template;
}