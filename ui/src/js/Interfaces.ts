export interface IMap {
    id: number;
    name: string;
    inuse: boolean;
    maxgrade: number;
    scale: string;
    createdby: string;
    createdat: string;
}

/**
 * Menu item - value/label, used all over
 */
export interface IMenuItem {
    value: string;
    label: string;
}

/**
 * See get_grade_item
 */
export interface IGradeitem {
    id: number;
    courseid: number;
    categoryid: number;
    itemname: string;
    itemtype: string;
    itemmodule: string;
    iteminstance: number;
    isscale: boolean;
    scalename: string;
    grademax: number;
    weight: number;
    categoryerror: boolean;
    link: string;
}

/**
 * Set get_capture_export_options
 */
export interface ICaptureExportOptions {
    gradetype: string;
    description?: string;
    selected: boolean;
}

/**
 * See upload_csv
 */
export interface IErrorList {
    error: string;
    count: number;
}

/**
 * see get_gradetypes
 */
export interface IGradetype {
    value: string;
    label: string;
}

/**
 * see get_leveloncategories
 */
export interface ICategories {
    id: number;
    fullname: string;
}

export interface IErrorItems {
    gradeitemid: number;
    itemname: string;
}

/**
 * See get_all_strings
 */
export interface IMoodleString {
    tag: string;
    stringvalue: string;
}

/**
 * See get_audit
 */
export interface IAuditItem {
    id: number;
    courseid: number;
    userid: number;
    username: string;
    relateduserid: number;
    relatedusername: string;
    gradeitemid: number;
    gradeitem: string;
    timecreated: number;
    time: string;
    message: string;
}

/**
 * See get_capture_page.
 */
export interface ICaptureColumn {
    id: number;
    gradetype: string;
    editable: boolean;
    description: string;
    other: string;
    points: boolean;
}

export interface ICaptureGrade {
    displaygrade: string;
    gradetype: string;
    columnid: number;
}

export interface ICaptureUser {
    id: number;
    displayname: string;
    firstinitial: string;
    lastinitial: string;
    pictureurl: string;
    profileurl: string;
    idnumber: string;
    alert: boolean;
    gradehidden: boolean;
    gradebookhidden: boolean;
    grades: ICaptureGrade[];
    awaitingcapture?: boolean;
    editcolumn?: boolean;
    reason?: string;
    other?: string;
    gradeitemid?: number;
}

/**
 * See get_groups
 */
export interface IGroup {
    id: number;
    courseid: number;
    name: string;
}

/**
 * See get_conversion_map
 */
export interface IConversionMap {
    band: string;
    bound: number;
    grade: number;
}

/**
 * See get_settings
 */
export interface ISetting {
    name: string;
    value: string;
}

/**
 * See get_aggregation_export_plugins
 */
export interface IAggregationExportPlugin {
    name: string;
    description: string;
}

/**
 * See get_aggregation_export_form
 */
export interface IAggregationExportForm {
    identifier: string;
    description: string;
    selected: boolean;
    category: boolean;
}

/**
 * See get_alter_weight_form
 */
export interface IAlterWeightItem {
    fullname: string;
    gradeitemid: number;
    gradetype: string;
    display: string;
    originalweight: number;
    alteredweight: number;
    isaltered: boolean;
}

/**
 * See save_altered_weights
 */
export interface ISaveAlteredWeightItem {
    gradeitemid: number;
    weight: number;
}

/**
 * Formkit
 *
 */
export interface IFormkitOption {
    value: string;
    label: string;
}

/**
 * CaptureSelect.vue
 */
export interface IEmitItemData {
    itemid: number;
    groupid: number;
    categoryid: number;
}

/**
 * AddMultipleButton.vue
 */
export interface IEmitEditColumn {
    columnname: string;
    gradetype: string;
    other: string;
    usescale: boolean;
    grademax: number;
    scalemenu: IMenuItem[];
    adminmenu: IMenuItem[];
    notes: string;
    columnid: number;
}