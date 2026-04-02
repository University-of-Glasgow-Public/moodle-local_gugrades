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
 * Se get_all_strings
 */
export interface IMoodleString {
    tag: string;
    stringvalue: string;
}