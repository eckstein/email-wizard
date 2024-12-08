// Template Management
export {
	createSingleTemplate,
	duplicateSingleTemplate,
	saveTemplateFormData,
	restoreTemplates,
	deleteTemplatesForever,
	templateTableAPI
} from './file-explorer/services/template-table-api';

// Folder Operations
export {
	selectFolder,
	openFolderTitleEditor,
	createNewWizardFolder,
	renameSingleFolder,
	initFileExplorer
} from './file-explorer/handlers/folders';

// Item Operations
export {
	moveItems,
	deleteItems,
	showDeleteConfirm,
	showRestoreConfirm
} from './file-explorer/core/common';

// Team Management
export {
	initTeamHandler,
	handleNewTeam
} from './teams/handlers/team-actions';

// Account Management
export {
	initAccountPage
} from './account/index';

export { initTeams } from './teams';
