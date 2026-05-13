<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <div>
        <div class="border rounded-md p-2 mt-2 border-gray-300">
            <div>
                <div class="badge badge-primary mb-4" @click="selectcollapse">
                    <span class="inline-flex items-center gap-1">
                        <CircleArrowUp v-if="collapsed" :size="18" />
                        <CircleArrowDown v-else :size="18" />
                        {{ collapsed ? mstrings['showcategories'] : mstrings['hidecategories'] }}
                    </span>
                </div>
            </div>

            <div id="captureselect" class="overflow-hidden transition-all duration-300"
                :class="collapsed ? 'max-h-0 opacity-0' : 'max-h-screen opacity-100'">
                <CaptureSelect @selecteditemid="selecteditemid"></CaptureSelect>

                <div v-if="itemid">
                    <CaptureAlerts
                        :gradesupported="gradesupported"
                        :aggregationsupported="aggregationsupported"
                        :unsupportedscales="unsupportedscales"
                        :gradehidden="gradehidden"
                        :gradelocked="gradelocked"
                        :noids="!showcsvimport"
                        >
                    </CaptureAlerts>

                    <CaptureButtons
                        v-if="gradesupported"
                        :loaded="loaded"
                        :itemid="itemid"
                        :groupid="groupid"
                        :userids="userids"
                        :users="users"
                        :itemtype="itemtype"
                        :itemname="itemname"
                        :usershidden="usershidden"
                        :gradesimported="gradesimported"
                        :showconversion="showconversion"
                        :converted="converted"
                        :released="released"
                        :revealnames="revealnames"
                        :showcsvimport="showcsvimport"
                        :staffuserid="staffuserid"
                        :caneditgrades="caneditgrades"
                        @refreshtable="refresh"
                        @viewfullnames="viewfullnames"
                        @editcolumn="editcog_clicked"
                        >
                    </CaptureButtons>
                </div>
            </div>
        </div>

        <div v-if="itemid && gradesupported" class="mt-2">
            <NameFilter v-if="!usershidden" @selected="filter_selected" ref="namefilterref"></NameFilter>

            <!-- Please wait spinner -->
            <PleaseWait v-if="!loaded"></PleaseWait>

            <div v-if="showtable && loaded">

                <!-- button for saving cell edits -->
                <div class="pb-1 flex justify-end" v-if="ineditcellmode">
                    <TwButton color="warning" @click="edit_cell_cancelled">{{ mstrings.cancel }}</TwButton>
                    <TwButton color="primary" @click="edit_cell_saved">{{ mstrings.save }}</TwButton>
                </div>

                <!-- Note. The array 'users' contains the lines of data. One record for each user -->
                <EasyDataTable
                    alternating
                    :key="datatablekey"
                    :current-page="currentpage"
                    sort-by="displayname"
                    sort-type="asc"
                    table-class-name="capture-table"
                    :items="users"
                    :headers="headers"
                    header-text-direction="center"
                    :body-row-class-name="table_row_class"
                    :body-item-class-name="table_item_class"
                    :header-item-class-name="header_item_class"
                    :filter-options="table_filter"
                    @update-page-items="pagination_change"
                    :rows-per-page="rowsperpage"
                    :rows-items="[25,50,100,250]"
                    :style="{ overflow: 'visible' }"
                    >

                    <!-- add header text and edit cog next to cell if required -->
                    <!-- component needs to return info about which column (which reason/gradetype has been selected)-->
                    <template #header="header">
                        {{ header.text }}
                        <CaptureColumnEditCog v-if="header.editable  && !ineditcellmode && caneditgrades" :header="header" :itemid="itemid" @editcolumn="editcog_clicked" @columnchanged="refresh"></CaptureColumnEditCog>
                    </template>

                    <!-- User picture column -->
                    <template #item-slotuserpicture="item">
                        <a :href="item.profileurl" class="avatar">
                            <img :src="item.pictureurl" :alt="item.displayname" class="rounded-full" width="35" height="35"/>
                        </a>
                    </template>

                    <!-- Provisional column -->
                    <template v-slot:[provisionalslot]="item">
                        <div v-if="item[provisionalid]">
                            <span v-if="item.gradehidden && !item.gradebookhidden" class="border-2 border-yellow-500 border-rounded-md p-1">{{ item[provisionalid] }}</span>
                            <span v-if="item.gradebookhidden" class="border-2 border-green-500 border-rounded-md p-1">{{ item[provisionalid] }}</span>
                            <span v-if="!item.gradebookhidden && !item.gradehidden">{{ item[provisionalid] }}</span>
                        </div>
                    </template>


                    <!-- switch to input for bulk editing (if selected) -->
                    <template v-slot:[editcolumnslot]="item">
                        <EditCaptureCell
                            :item="item"
                            :column="editcolumn"
                            :columnid="editcolumnid"
                            :other="editother"
                            :notes="editnotes"
                            :gradeitemid="itemid"
                            :categoryid="categoryid"
                            :gradetype="editgradetype"
                            :usescale="editusescale"
                            :scalemenu="editscalemenu"
                            :adminmenu="editadminmenu"
                            :grademax="editgrademax"
                            :cancelled="editcancelled"
                            @gradewritten = "edit_grade_written()"
                            @gradecancel = "edit_grade_written()"
                             >
                        </EditCaptureCell>
                    </template>

                    <!-- dropdown in the final column -->
                    <template #item-actions="item">
                        <CaptureMenu
                            v-if="!ineditcellmode"
                            :item="item"
                            :itemid="itemid"
                            :categoryid="categoryid"
                            :userid="parseInt(item.id)"
                            :name="item.displayname"
                            :itemname="itemname"
                            :gradesimported="gradesimported"
                            :awaitingcapture="item.awaitingcapture"
                            :gradehidden="item.gradehidden"
                            :converted="converted"
                            :caneditgrades="caneditgrades"
                            @gradeadded = "get_user_data(item.id)"
                            >
                        </CaptureMenu>
                    </template>

                    <!-- show warning if grades do not agree -->
                    <template #item-alert="item">
                        <div class="capture-warning">
                            <div v-if="item.alert" class="badge badge-error mb-1 mr-1">{{ mstrings['discrepancy'] }}</div>
                            <div v-if="item.gradebookhidden" class="badge badge-success mb-1 mr-1">{{ mstrings['hiddengradebook'] }}</div>
                            <div v-if="item.gradehidden" class="badge badge-warning mb-1">{{ mstrings['hiddenmygrades'] }}</div>
                        </div>
                    </template>

                    <!-- Override pagination if bulk editing -->
                    <template #pagination>
                        <span v-if="ineditcellmode">{{ mstrings['pleasesavefirst'] }}</span>
                        <TablePagination v-else
                            :rowsPerPage="rowsperpage"
                            :rowsCount="users.length"
                            :startPage="currentpage"
                            @pagechange="page_changed"
                        ></TablePagination>
                    </template>
                </EasyDataTable>

                <!-- button for saving cell edits -->
                <div class="pb-1 mt-2 flex justify-end" v-if="ineditcellmode">
                    <TwButton color="warning" @click="edit_cell_cancelled">{{ mstrings.cancel }}</TwButton>
                    <TwButton color="primary" @click="edit_cell_saved">{{ mstrings.save }}</TwButton>
                </div>
            </div>

            <h2 v-if="!showtable">{{ mstrings['nothingtodisplay'] }}</h2>
        </div>
    </div>
</template>

<script setup lang="ts">
    import {ref, computed, watch, onMounted} from 'vue';
    import { storeToRefs } from 'pinia';
    import NameFilter from '@/components/Common/NameFilter.vue';
    import CaptureSelect from '@/components/Capture/CaptureSelect.vue';
    import CaptureMenu from '@/components/Capture/CaptureMenu.vue';
    import { useToast } from "vue-toastification";
    import CaptureButtons from '@/components/Capture/CaptureButtons.vue';
    import CaptureAlerts from '@/components/Capture/CaptureAlerts.vue';
    import CaptureColumnEditCog from '@/components/Capture/CaptureColumnEditCog.vue';
    import EditCaptureCell from '@/components/Capture/EditCaptureCell.vue';
    import PleaseWait from '@/components/Common/PleaseWait.vue';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import { useLogo } from '@/js/monochromelogo.js';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import type { Header, Item } from "vue3-easy-data-table";
    import TablePagination from '@/components/Common/TablePagination.vue';
    import TwButton from '@/components/Tailwind/TwButton.vue';
    import { CircleArrowDown, CircleArrowUp } from '@lucide/vue';
    import { watchDebounced } from '@vueuse/core';
    import type { IEmitItemData, IEmitEditColumn, IMenuItem, ICaptureColumn, ICaptureUser, ICaptureGrade } from '@/js/Interfaces';

    const users = ref< ICaptureUser[] >([]);
    const userids = ref< number[] >([]);
    const itemid = ref(0);
    const categoryid = ref(0);
    const groupid = ref(0);
    const totalrows = ref(0);
    const currentpage = ref(1);
    const rowsperpage = ref(25);
    const datatablekey = ref(1);
    const usershidden = ref(false);
    const namefilterref = ref(null);
    const itemtype = ref('');
    const itemname = ref('');
    const gradesupported = ref(true);
    const aggregationsupported = ref(true);
    const unsupportedscales = ref('');
    const gradesimported = ref(false);
    const gradehidden = ref(false);
    const gradelocked = ref(false);
    const converted = ref(false);
    const released = ref(false);
    const columns = ref< ICaptureColumn[] >([]);
    const loaded = ref(false);
    const showalert = ref(false);
    const revealnames = ref(false);
    const collapsed = ref(false);
    const editcolumn = ref('');
    const editcolumnslot = ref('');
    const editusescale = ref(false);
    const editscalemenu = ref< IMenuItem[] >([]);
    const editadminmenu = ref< IMenuItem[] >([]);
    const editgradetype = ref('');
    const editgrademax = ref(0);
    const editgradecount = ref(0);
    const editcolumnid = ref(0);
    const editother = ref('');
    const editnotes = ref('');
    const editcancelled = ref(false);
    const showconversion = ref(false);
    const provisionalslot = ref('');
    const provisionalid = ref('');
    const showcsvimport = ref(true);
    const debug = ref({});
    const firstname = ref('');
    const lastname = ref('');
    const staffuserid = ref(0);
    const collapseclasses = ref(['collapse', 'show']);
    const caneditgrades = ref(false);
    const toast = useToast();
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );
    // pagination related.
    const dataTable = ref();
    const props = {
        dataTable: dataTable,
    }
    const {monochrome, updateLogo} = useLogo();

    /**
     * onMounted, get write grades capability
     */
    onMounted(() => {

        moodleFetch(
            'local_gugrades_has_capability',
            {
                capability: 'local/gugrades:editgrades'
            }
        )
        .then((result: any) => {
            caneditgrades.value = result.hascapability;
        })
        .catch((error) => {
            window.console.log(error);
            debug.value = error;
        });
    });

    /**
     * Page changed by pagination
     */
    function page_changed(newpage: number) {
        currentpage.value = newpage;
        datatablekey.value++;
    }

    /**
     * Number of pages changed
     */
    function pagination_change(rows: any) {
        rowsperpage.value = rows.length;
        currentpage.value = 1;
        datatablekey.value++;
    }

    /**
     * Table name filter
     */
    const table_filter = computed(() => {
        const options = [];

        if (firstname.value != '') {
            options.push({
                field: 'firstinitial',
                comparison: '=',
                criteria: firstname.value,
            });
        }

        if (lastname.value != '') {
            options.push({
                field: 'lastinitial',
                comparison: '=',
                criteria: lastname.value,
            });
        }

        return options;
    });

    /**
     * A watch for the itemid changing
     * Lots of stuff gets reset if the itemid is changed.
     */
    watch(itemid, () => {
        currentpage.value = 1;
        revealnames.value = false;
        editcolumn.value = '';
        editcolumnslot.value = '';
    });

    /**
     * Reset the page
     */
    function reset_page() {
        usershidden.value = false;
        users.value = [];
        itemtype.value = '';
        itemname.value = '';
        gradesupported.value = true;
        gradesimported.value = false;
        gradehidden.value = false;
        gradelocked.value = false;
        columns.value = [];
        userids.value = [];
        totalrows.value = 0;
        showconversion.value = false;
        converted.value = false;
        released.value = false;
        loaded.value = false;
    }

    /**
     * Get class name for table row depending on criteria
     * Used to show hidden rows
     */
    function table_row_class(item: Item) {
        return 'non-hidden-row'
        if (item.gradehidden) {
            return 'hidden-row';
        } else if (item.gradebookhidden) {
            return 'gradebookhidden-row';
        } else {
            return 'non-hidden-row';
        }
    }

    /**
     * Get class name for table items
     */
    function table_item_class(column: string) {

        // Hide name initial columns
        if ((column == 'firstinitial') || (column == 'lastinitial')) {
            return 'hidden';
        }
        if (column != 'displayname') {
            return '!text-center';
        }
    }

    /**
     * Get class name for header items
     */
     function header_item_class(header: Header) {
        if ((header.value == 'firstinitial') || (header.value == 'lastinitial')) {
            return 'hidden';
        }
    }

    /**
     * Collapse selection area
     */
    function selectcollapse() {

        collapsed.value = !collapsed.value;
    }

    /**
     * New itemid and/or groupid has been selected
     * If itemid = 0, then reset the table
     */
    function selecteditemid(itemgroup: IEmitItemData) {
        itemid.value = itemgroup.itemid;
        groupid.value = itemgroup.groupid;
        categoryid.value = itemgroup.categoryid;

        if (itemid.value == 0) {
            reset_page();
        } else {
            reload_page();
        }
    }

    /**
     * Column editcog has been clicked
     */
     function editcog_clicked(cellform: IEmitEditColumn) {

        // Unpack data
        const columnname = cellform.columnname;

        // Note: this is the EasyDataTable slot name for the column.
        editcolumnslot.value = 'item-' + columnname;
        editcolumn.value = columnname;
        editusescale.value = cellform.usescale;
        editscalemenu.value = cellform.scalemenu;
        editadminmenu.value = cellform.adminmenu;
        editgradetype.value = cellform.gradetype;
        editgrademax.value = cellform.grademax;
        editcolumnid.value = cellform.columnid;
        editother.value = cellform.other;
        editnotes.value = cellform.notes;
        editcancelled.value = false;
        reload_page();
    }

    /**
     * In edit mode, the save button is clicked
     * The cells save themselves using a hook on before close.
     */
    function edit_cell_saved() {
        editcolumn.value = '';
        editcolumnslot.value = '';
    }

    /**
     * In edit mode, the cancel button is clicked
     * Set editcancelled to true and pass as prop to edit cells
     * so it knows not to save.
     */
    function edit_cell_cancelled() {
        editcancelled.value = true;
    }

    /**
     * A cell has declared that it has been written (or cancelled)
     * (We're probably getting lots of these)
     * Just count them and we'll watch/debounce the count to update the table
     */
    function edit_grade_written() {
        editgradecount.value++;
    }

    /**
     * See above - watching edit cell written count in order to
     * upgrade the main table
     */
     watchDebounced(
        editgradecount,
        () => {

            // Duplicated for cancel
            editcolumn.value = '';
            editcolumnslot.value = '';

            reload_page();
        },
        { debounce: 500, maxWait: 1000 },
    );

    /**
     * Are we in "edit a cell" mode?
     * Stuff doesn't appear, if so, and 'Save' button appears.
     */
    const ineditcellmode = computed(() => {
        return editcolumn.value != '';
    });

    /**
     * Get headers for table
     * These also define what data is displayed.
     */
    const headers = computed(() => {
        let heads = [];
        if (!usershidden.value) {
            heads.push({text: 'firstinitial', value: 'firstinitial'}),
            heads.push({text: 'lastinitial', value: 'firstinitial'}),
            heads.push({text: mstrings.value['userpicture'], value: "slotuserpicture"});
            heads.push({text: mstrings.value['firstnamelastname'], value: "displayname", sortable: true})
        } else {
            heads.push({text: mstrings.value['participant'], value: "displayname", sortable: true});
        }
        heads.push({text: mstrings.value['idnumber'], value: "idnumber", sortable: true});
        if (showalert.value) {
            heads.push({text: mstrings.value['warnings'], value: "alert"});
        }

        // Add the grades columns
        columns.value.forEach(column => {

            // grab the value of the provisional column
            // We'll use it to style the column in the table.
            if (column.gradetype == 'PROVISIONAL') {
                provisionalslot.value = 'item-GRADE' + column.id;
                provisionalid.value = 'GRADE' + column.id;
            }

            // Make sure that the value is a string
            heads.push({
                text: column.description,
                value: 'GRADE' + column.id,
                gradetype: column.gradetype,
                editable: column.editable,
                columnid: column.id,
                other: column.other,
            });
        });

        // Space for the buttons column
        heads.push({text: mstrings.value['actions'], value: "actions"});

        return heads;
    });

    /**
     * Handle viewfullnames
     * @param bool toggleview
     */
    function viewfullnames(toggleview: boolean) {
        revealnames.value = toggleview;
        reload_page();
    }

    /**
     * Add the column/grade data for individual user
     *
     */
    function add_user_grades(user: ICaptureUser, columns: ICaptureColumn[]) {
        let grade: Partial<ICaptureGrade> | undefined = {};

        // Only show alert/discrepancy column if there are any
        if (user.alert || user.gradebookhidden || user.gradehidden) {
            showalert.value = true;
        }

        // Allow import if there are no grades for this user.
        user.awaitingcapture = true;
        columns.forEach(column => {
            const columnname = 'GRADE' + column.id;
            grade = user.grades.find((element) => {
                return (element.columnid == column.id);
            });
            if (grade) {
                if ((grade['displaygrade'] != 'No grade') && (grade['displaygrade'] != 'Awaiting capture')) {
                    user.awaitingcapture = false;
                }
                user[columnname] = grade['displaygrade'];
            } else if (column.gradetype == 'FIRST') {
                user[columnname] = mstrings.value['awaitingcapture'];
            } else {
                user[columnname] = '';
            }

            // Is this column in 'editing mode'?
            // If so, we add the 'editcolumn' ta (true) to each cell in that column
            // The table slot can then pick it up and display an edit box
            // Similarly the reason/gradetype stuff
            user.editcolumn = (columnname == editcolumn.value);
            user.reason = column.gradetype;
            user.other = column.other;

            // TODO: gradeitemid doesn't appear to be in the columns array
            //user.gradeitemid = column.gradeitemid;
        });

        return user;
    }

    /**
     * Add grade columns into 'users' data so the table component can display them
     * @param users
     * @param columns
     * @return array
     */
    function add_grades(users: ICaptureUser[], columns: ICaptureColumn[]) {

        showalert.value = false;
        users.forEach(user => {

            add_user_grades(user, columns);
        });

        return users;
    }

    /**
     * Helper function to reload the page
     * (We have to do this in lots of places)
     */
    function reload_page() {
        get_page_data(itemid.value, groupid.value);
    }

    /**
     * Get filtered/paged data
     * @param int itemid
     * @param char first
     * @param char last
     * @param int gid (group id)
     */
     function get_page_data(itemid: number, gid: number) {
        loaded.value = false;

        moodleFetch(
            'local_gugrades_get_capture_page',
            {
                gradeitemid: itemid,
                firstname: '',
                lastname: '',
                groupid: gid,
                viewfullnames: revealnames.value,
            }
        )
        .then((result: any) => {
            usershidden.value = result.hidden;
            users.value = result.users;
            itemtype.value = result.itemtype;
            itemname.value = result.itemname;
            gradesupported.value = result.gradesupported;
            aggregationsupported.value = result.aggregationsupported;
            unsupportedscales.value = result.unsupportedscales;
            gradesimported.value = result.gradesimported;
            gradehidden.value = result.gradehidden;
            gradelocked.value = result.gradelocked;
            columns.value = result.columns;
            userids.value = users.value.map(u => u.id);
            totalrows.value = users.value.length;
            showconversion.value = result.showconversion;
            converted.value = result.converted;
            released.value = result.released;
            showcsvimport.value = result.showcsvimport;
            staffuserid.value = result.staffuserid;

            users.value = add_grades(users.value, columns.value);

            loaded.value = true;
        })
        .catch((error) => {
            console.error(error);
            debug.value = error;
        });
    }

    /**
     * Work out if user data contains any additional columns
     * If a new column has been added then
     * Return true if missing columns
     */
    function missing_columns(usergrades: ICaptureGrade[]) {

        // Flag a missing gradetype inside the callback
        let missing = false;

        usergrades.forEach((grade) => {
            const gradetype = grade.gradetype;
            const found = columns.value.find((column) => {
                return column.gradetype == gradetype;
            });

            // found returns undefined if not found. Only need one to be not found
            if (found === undefined) {
                missing = true;
            }
        });

        return missing;
    }

    /**
     * Get the data for an individual user
     * (If grade added and so on)
     */
    function get_user_data(userid: number) {

        moodleFetch(
            'local_gugrades_get_capture_user',
            {
                gradeitemid: itemid.value,
                userid: userid,
                viewfullnames: revealnames.value,
            }
        )
        .then((result: any) => {
            const updateduser = add_user_grades(result, columns.value);

            // If this seems to have added more columns then do a page reload.
            if (missing_columns(updateduser.grades)) {
                reload_page();
            } else {
                const found = users.value.findIndex((user) => {
                    return user.id == updateduser.id;
                });
                if (found > -1) {
                    users.value[found] = result;
                }
            }
        })
        .catch((error) => {
            window.console.error(error);
            debug.value = error;
        });
    }

    /**
     * Firstname/lastname filter selected
     * @param {*} first
     * @param {*} last
     */
    function filter_selected(first: string, last: string) {
        if (first == 'all') {
            first = '';
        }
        if (last == 'all') {
            last = '';
        }
        firstname.value = first;
        lastname.value = last;

        // Reset page
        //currentpage.value = 1;
        //get_page_data(itemid.value, groupid.value);
    }

    /**
     * Refresh the data table
     */
    function refresh() {
        get_page_data(itemid.value, groupid.value);
        updateLogo();
    }

    /**
     * Show table if there's anything to show
     */
    const showtable = computed(() => {
        return users.value.length != 0;
    });

</script>

<style>
/*
    .hidden-row td {
        background-color: #ffff66  !important;
        overflow: visible;
    }

    .gradebookhidden-row td {
        background-color: #fabbec!important;
        overflow: visible;
    }

    .non-hidden-row td {
        overflow: visible;
    }

    .capture-table {
        --easy-table-header-font-size: 14px;
        --easy-table-header-height: 50px;
        --easy-table-header-font-color: #f5f5f5;
        --easy-table-header-background-color: #005c8a;

        --easy-table-header-item-padding: 10px 15px;
    }

    .border-lg {
        border-width: thick !important;
    }

    .capture-warning {
        font-size: 125%;
    }

    .buttons-pagination .item.button.active {
        color: black !important;
    }

    .vue3-easy-data-table__main {
        overflow: visible !important;
    }
        */

    .capture-table {
        --easy-table-header-background-color: var(--color-primary);
        --easy-table-header-font-color: var(--color-primary-content);

        --easy-table-body-row-background-color: var(--color-base-100);
        --easy-table-body-row-font-color: var(--color-base-content);

        --easy-table-body-even-row-background-color: var(--color-base-300);
        --easy-table-body-even-row-font-color: var(--color-base-content);

        --easy-table-footer-background-color: var(--color-primary);
        --easy-table-footer-font-color: var(--color-primary-content);
    }

    .vue3-easy-data-table__main {
        overflow: visible !important;
    }
</style>