<?php

/**
 * Class WizardFormFieldGenerator
 * Handles the generation of form fields and fieldsets for the wizard interface.
 */
class WizardFormFieldGenerator {
	/**
	 * Supported input types
	 * @var array
	 */
	private $supportedInputTypes = [
		'text', 'email', 'number', 'password', 'search', 'url'
	];

	/**
	 * Generates a complete fieldset section with title and content
	 *
	 * @param string $sectionId The ID of the section
	 * @param string $sectionTitle The title of the section
	 * @param array $fieldData Array of field configurations
	 * @return string Generated HTML for the fieldset
	 */
	public function generateTemplateDataFieldset(string $sectionId, string $sectionTitle, array $fieldData): string {
		if (empty($sectionId) || empty($fieldData)) {
			return '';
		}

		return "
			<fieldset class=\"wizard-form-section\" id=\"{$sectionId}\">
				<h3 class=\"wizard-form-section-title\">{$sectionTitle}</h3>
				<div class=\"wizard-form-content\">
					{$this->generateWizardFieldgroup($fieldData)}
				</div>
			</fieldset>
		";
	}

	/**
	 * Generates form fields based on field configuration
	 *
	 * @param array $fieldData Array of field configurations
	 * @param string $namePrefix Prefix for field names (used in nested structures)
	 * @return string Generated HTML for the fields
	 */
	public function generateWizardFieldgroup(array $fieldData, string $namePrefix = ''): string {
		if (empty($fieldData)) {
			return '';
		}

		$template = '';

		foreach ($fieldData as $field) {
			if (!isset($field['id'])) {
				continue;
			}

			$fieldType = $field['type'] ?? 'text';
			$fieldLabel = $field['label'] ?? '';
			$fieldValue = $field['value'] ?? '';
			$placeholder = $field['placeholder'] ?? '';
			$disabled = isset($field['disabled']) && $field['disabled'] === true ? 'disabled' : '';
			$description = $field['description'] ?? '';

			$methodName = "generate" . $this->formatFieldType($fieldType) . "Field";
			if (method_exists($this, $methodName)) {
				$template .= $this->$methodName(
					$field,
					$namePrefix,
					$fieldLabel,
					$fieldValue,
					$placeholder,
					$disabled,
					$description
				);
			}
		}

		return $template;
	}

	/**
	 * Formats the field type string for method name
	 */
	private function formatFieldType(string $type): string {
		return str_replace('-', '', ucwords($type, '-'));
	}

	/**
	 * Generates description HTML if description exists
	 */
	private function getDescriptionHtml(string $description): string {
		return !empty($description) ? "<div class=\"field-description\">{$description}</div>" : '';
	}

	/**
	 * Generates a standard input field (text, email, number, etc.)
	 */
	private function generateInputField(array $field, string $namePrefix, string $label, string $value, string $placeholder, string $disabled, string $description, string $type = 'text'): string {
		if (!in_array($type, $this->supportedInputTypes)) {
			$type = 'text';
		}

		return "
			<div class=\"wizard-form-fieldgroup {$type}\" id=\"template-{$field['id']}\">
				<label class=\"wizard-form-fieldgroup-label\">{$label}</label>
				<div class=\"wizard-form-fieldgroup-value\">
					<input type=\"{$type}\" 
						   class=\"wizard-form-fieldgroup-input\" 
						   name=\"{$namePrefix}[{$field['id']}]\" 
						   value=\"{$value}\"
						   placeholder=\"{$placeholder}\" 
						   {$disabled}>
					{$this->getDescriptionHtml($description)}
				</div>
			</div>
		";
	}

	/**
	 * Generates specific input type fields
	 */
	private function generateTextField(...$args): string { 
		$args[] = 'text';
		return $this->generateInputField(...$args); 
	}
	private function generateEmailField(...$args): string { 
		$args[] = 'email';
		return $this->generateInputField(...$args); 
	}
	private function generateNumberField(...$args): string { 
		$args[] = 'number';
		return $this->generateInputField(...$args); 
	}
	private function generatePasswordField(...$args): string { 
		$args[] = 'password';
		return $this->generateInputField(...$args); 
	}
	private function generateSearchField(...$args): string { 
		$args[] = 'search';
		return $this->generateInputField(...$args); 
	}
	private function generateUrlField(...$args): string { 
		$args[] = 'url';
		return $this->generateInputField(...$args); 
	}

	/**
	 * Generates a textarea field
	 */
	private function generateTextareaField(array $field, string $namePrefix, string $label, string $value, string $placeholder, string $disabled, string $description): string {
		return "
			<div class=\"wizard-form-fieldgroup textarea\" id=\"template-{$field['id']}\">
				<label class=\"wizard-form-fieldgroup-label\">{$label}</label>
				<div class=\"wizard-form-fieldgroup-value\">
					<textarea class=\"wizard-form-fieldgroup-input\" 
							  name=\"{$namePrefix}[{$field['id']}]\" 
							  placeholder=\"{$placeholder}\" 
							  {$disabled}>{$value}</textarea>
					{$this->getDescriptionHtml($description)}
				</div>
			</div>
		";
	}

	/**
	 * Generates a select dropdown field
	 */
	private function generateSelectField(array $field, string $namePrefix, string $label, string $value, string $placeholder, string $disabled, string $description): string {
		$options = $field['options'] ?? [];
		$optionsHtml = '';
		
		foreach ($options as $option) {
			$selected = $option['value'] == $value ? 'selected' : '';
			$optionsHtml .= "<option value=\"{$option['value']}\" {$selected}>{$option['label']}</option>";
		}

		return "
			<div class=\"wizard-form-fieldgroup select\" id=\"template-{$field['id']}\">
				<label class=\"wizard-form-fieldgroup-label\">{$label}</label>
				<div class=\"wizard-form-fieldgroup-value\">
					<select class=\"wizard-form-fieldgroup-input\" 
							name=\"{$namePrefix}[{$field['id']}]\" 
							{$disabled}>
						{$optionsHtml}
					</select>
					{$this->getDescriptionHtml($description)}
				</div>
			</div>
		";
	}

	/**
	 * Generates a checkbox field
	 */
	private function generateCheckboxField(array $field, string $namePrefix, string $label, string $value, string $placeholder, string $disabled, string $description): string {
		$checked = $value ? 'checked' : '';
		return "
			<div class=\"wizard-form-fieldgroup checkbox\" id=\"template-{$field['id']}\">
				<label class=\"wizard-form-fieldgroup-label\">{$label}</label>
				<div class=\"wizard-form-fieldgroup-value\">
					<input type=\"checkbox\" 
						   class=\"wizard-form-fieldgroup-value\" 
						   name=\"{$namePrefix}[{$field['id']}]\" 
						   {$checked} 
						   {$disabled}>
					{$this->getDescriptionHtml($description)}
				</div>
			</div>
		";
	}

	/**
	 * Generates a radio button field
	 */
	private function generateRadioField(array $field, string $namePrefix, string $label, string $value, string $placeholder, string $disabled, string $description): string {
		$options = $field['options'] ?? [];
		$optionsHtml = '';

		foreach ($options as $option) {
			$checked = $option['value'] == $value ? 'checked' : '';
			$optionsHtml .= "
				<label>
					<input type=\"radio\" 
						   name=\"{$namePrefix}[{$field['id']}]\" 
						   value=\"{$option['value']}\" 
						   {$checked} 
						   {$disabled}>
					{$option['label']}
				</label>
			";
		}

		return "
			<div class=\"wizard-form-fieldgroup radio\" id=\"template-{$field['id']}\">
				<label class=\"wizard-form-fieldgroup-label\">{$label}</label>
				<div class=\"wizard-form-fieldgroup-value\">
					<div class=\"wizard-form-fieldgroup-input\">
						{$optionsHtml}
					</div>
					{$this->getDescriptionHtml($description)}
				</div>
			</div>
		";
	}

	/**
	 * Generates a checkbox group field
	 */
	private function generateCheckboxGroupField(array $field, string $namePrefix, string $label, array $value, string $placeholder, string $disabled, string $description): string {
		$options = $field['options'] ?? [];
		$optionsHtml = '';

		foreach ($options as $option) {
			$checked = in_array($option['value'], (array)$field['value']) ? 'checked' : '';
			$optionsHtml .= "
				<label>
					<input type=\"checkbox\" 
						   name=\"{$namePrefix}[{$field['id']}][]\" 
						   value=\"{$option['value']}\" 
						   {$checked} 
						   {$disabled}>
					{$option['label']}
				</label>
			";
		}

		return "
			<div class=\"wizard-form-fieldgroup checkbox-group\" id=\"template-{$field['id']}\">
				<label class=\"wizard-form-fieldgroup-label\">{$label}</label>
				<div class=\"wizard-form-fieldgroup-value\">
					{$optionsHtml}
					{$this->getDescriptionHtml($description)}
				</div>
			</div>
		";
	}

	/**
	 * Generates a radio group field
	 */
	private function generateRadioGroupField(array $field, string $namePrefix, string $label, string $value, string $placeholder, string $disabled, string $description): string {
		$options = $field['options'] ?? [];
		$optionsHtml = '';

		foreach ($options as $option) {
			$checked = $option['value'] == $field['value'] ? 'checked' : '';
			$optionsHtml .= "
				<label>
					<input type=\"radio\" 
						   name=\"{$namePrefix}[{$field['id']}]\" 
						   value=\"{$option['value']}\" 
						   {$checked} 
						   {$disabled}>
					{$option['label']}
				</label>
			";
		}

		return "
			<div class=\"wizard-form-fieldgroup radio-group\" id=\"template-{$field['id']}\">
				<label class=\"wizard-form-fieldgroup-label\">{$label}</label>
				<div class=\"wizard-form-fieldgroup-value\">
					{$optionsHtml}
					{$this->getDescriptionHtml($description)}
				</div>
			</div>
		";
	}

	/**
	 * Generates a repeater field
	 */
	private function generateRepeaterField(array $field, string $namePrefix, string $label, $value, string $placeholder, string $disabled, string $description): string {
		$fields = $field['fields'] ?? [];
		$display = $field['display'] ?? '';
		$rowValues = $field['value'] ?? [];
		
		$template = "
			<div class=\"wizard-form-fieldgroup repeater\" id=\"template-{$field['id']}\">
				<label class=\"wizard-form-fieldgroup-label\">{$label}</label>
				<div class=\"wizard-form-fieldgroup-value\">
					<div class=\"repeater-rows\">
		";

		if (empty($rowValues)) {
			$template .= '<div class="empty-repeater-message"><em>Click "Add Row" to enter data</em></div>';
		} else {
			foreach ($rowValues as $rowIndex => $rowValue) {
				$template .= "
					<div class=\"repeater-row {$display}\">
						" . $this->generateWizardFieldgroup($fields, "{$field['id']}[{$rowIndex}]") . "
						<button type=\"button\" class=\"wizard-button small red remove-row\"><i class=\"fa-solid fa-xmark\"></i></button>
					</div>
				";
			}
		}

		// Template for new rows
		$template .= "
				<div class=\"repeater-row-template {$display}\">
					" . $this->generateWizardFieldgroup($fields, "{$field['id']}[{{row}}]") . "
					<button type=\"button\" class=\"wizard-button small red remove-row\"><i class=\"fa-solid fa-xmark\"></i></button>
				</div>
			</div>
			<a href=\"#\" class=\"add-row\">Add row</a>
			</div>
		</div>
		";

		return $template;
	}
}