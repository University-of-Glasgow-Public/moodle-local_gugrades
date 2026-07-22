<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <div>
        <div class="bg-brand-light-purple/10 border rounded-md mt-2 border-gray-300 shadow-sm">

            <div id="captureselect" class="p-2 overflow-hidden transition-all duration-300"
                :class="collapsed ? 'max-h-0 opacity-0' : 'max-h-screen opacity-100'">
                <CaptureSelect @selecteditemid="selecteditemid"></CaptureSelect>

                <div v-if="itemid">
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

            <CaptureAlerts
                v-if="itemid"
                :gradesupported="gradesupported"
                :aggregationsupported="aggregationsupported"
                :unsupportedscales="unsupportedscales"
                :gradehidden="gradehidden"
                :gradelocked="gradelocked"
                :noids="!showcsvimport"
                >
            </CaptureAlerts>
        </div>



        <div v-if="itemid && gradesupported" class="mt-2">

            <!-- Please wait spinner -->
            <PleaseWait v-if="!loaded"></PleaseWait>

            <div v-if="showtable && loaded">

                <!-- button for saving cell edits -->
                <!--
                <div class="pb-1 flex justify-end gap-2" v-if="ineditcellmode">
                    <UButton variant="warning" @click="edit_cell_cancelled">{{ mstrings.cancel }}</UButton>
                    <UButton variant="primary" @click="edit_cell_saved">{{ mstrings.save }}</UButton>
                </div>
                -->

                <!-- NEW TANSTACK TABLE -->
                <UTable 
                    :data="users"
                    :columns="tablecolumns" 
                    :filters="tablefilters"
                    :visibility="{firstinitial: false, lastinitial: false}"
                    class="my-8"
                />

                <!-- Note. The array 'users' contains the lines of data. One record for each user -->
                <EasyDataTable
                    v-if="false"
                    alternating
                    buttons-pagination
                    :current-page="currentpage"
                    sort-by="displayname"
                    sort-type="asc"
                    table-class-name="uofg-table"
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
                    ref="dataTable"
                    >

                    <!-- add header text and edit cog next to cell if required -->
                    <!-- component needs to return info about which column (which reason/gradetype has been selected)-->
                    <template #header="header">
                        {{ header.text }}
                        <CaptureColumnEditCog v-if="header.editable  && !ineditcellmode && caneditgrades" :header="header" :itemid="itemid" @editcolumn="editcog_clicked" @columnchanged="refresh"></CaptureColumnEditCog>
                    </template>

                    <!-- notes -->
                     <template #item-slotnote="item">
                        <NoteButton
                            :gradeitemid="itemid"
                            :userid="item.id"
                            :name="item.displayname + ' for ' + itemname"
                            :shortnote="item.shortnote"
                            @updated="get_user_data(item.id)"
                            />
                     </template>

                    <!-- User picture column -->
                    <template #item-slotuserpicture="item">
                        <a :href="item.profileurl" class="avatar">
                            <img :src="item.pictureurl" :alt="item.displayname" class="rounded-full" width="35" height="35"/>
                        </a>
                    </template>

                    <!-- Latest grade column -->
                    <template v-slot:[provisionalslot]="item">
                        <div v-if="item[provisionalid]" class="text-sm font-medium">
                            
                            <span 
                                v-if="item.gradehidden && !item.gradebookhidden" 
                                class="inline-block border-2 border-brand-light-yellow rounded-md px-2 py-0.5 bg-brand-light-yellow/10 text-brand-dark-purple"
                            >
                                {{ item[provisionalid] }}
                            </span>

                            <span 
                                v-if="item.gradebookhidden" 
                                class="inline-block border-2 border-brand-light-green rounded-md px-2 py-0.5 bg-brand-light-green/10 text-brand-dark-purple"
                            >
                                {{ item[provisionalid] }}
                            </span>

                            <span 
                                v-if="!item.gradebookhidden && !item.gradehidden"
                                class="text-brand-dark-purple"
                            >
                                {{ item[provisionalid] }}
                            </span>

                        </div>
                    </template>

                    <!-- Grade display, or bulk-edit inputs when a column is selected -->
                    <template v-for="column in columns" :key="column.id" v-slot:['item-GRADE'+column.id]="item">
                        <EditCaptureCell
                            v-if="editcolumn === ('GRADE' + column.id)"
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
                            :shouldsave="editsaving"
                            @gradewritten="edit_grade_written()"
                            @gradecancel="edit_grade_written()"
                            @validitychange="edit_validity_change"
                        />
                        <GradeColor v-else :grade="item['GRADE' + column.id]" />
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
                        <div class="flex flex-wrap gap-1.5 items-center">
                            <CaptureWarning v-if="item.alert" variant="discrepancy">
                                {{ mstrings['discrepancy'] }}
                            </CaptureWarning>
                            
                            <CaptureWarning v-if="item.gradebookhidden" variant="gradebook-hidden">
                                {{ mstrings['hiddengradebook'] }}
                            </CaptureWarning>
                            
                            <CaptureWarning v-if="item.gradehidden" variant="grade-hidden">
                                {{ mstrings['hiddenmygrades'] }}
                            </CaptureWarning>
                        </div>
                    </template>

                    <!-- Override pagination if bulk editing -->
                    <template v-if="ineditcellmode" #pagination>
                        <span>{{ mstrings['pleasesavefirst'] }}</span>
                    </template>
                </EasyDataTable>

                <!-- button for saving cell edits -->
                <!--
                <div class="pb-1 mt-2 flex gap-2 justify-end" v-if="ineditcellmode">
                    <UButton variant="warning" @click="edit_cell_cancelled">{{ mstrings.cancel }}</UButton>
                    <UButton variant="primary" @click="edit_cell_saved">{{ mstrings.save }}</UButton>
                </div>
                -->
            </div>

            <h2 v-if="!showtable">{{ mstrings['nothingtodisplay'] }}</h2>
        </div>
    </div>
</template>

<script setup lang="ts">
    import {ref, computed, watch, onMounted, nextTick, h } from 'vue';
    import { storeToRefs } from 'pinia';
    import CaptureSelect from '@/components/Capture/CaptureSelect.vue';
    import CaptureMenu from '@/components/Capture/CaptureMenu.vue';
    import { useToast } from "vue-toastification";
    import CaptureButtons from '@/components/Capture/CaptureButtons.vue';
    import CaptureAlerts from '@/components/Capture/CaptureAlerts.vue';
    import CaptureColumnEditCog from '@/components/Capture/CaptureColumnEditCog.vue';
    //import EditCaptureCell from '@/components/Capture/EditCaptureCell.vue';
    import PleaseWait from '@/components/Common/PleaseWait.vue';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import { useLogo } from '@/js/monochromelogo.js';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import type { Header, Item } from "vue3-easy-data-table";
    import UButton from '@/components/Common/UButton.vue';
    import GradeColor from '@/components/Common/GradeColor.vue';
    import { watchDebounced } from '@vueuse/core';
    import type { IEmitItemData, IEmitEditColumn, IMenuItem, ICaptureColumn, ICaptureUser, ICaptureGrade, ICaptureCellForm } from '@/js/Interfaces';
    import NoteButton from '@/components/Common/NoteButton.vue';
    import { useFilter } from '@/stores/filter';
    import CaptureWarning from '@/components/Capture/CaptureWarning.vue';
    import UTable from '@/components/Common/UTable.vue';
    import { createColumnHelper, type ColumnFiltersState } from '@tanstack/vue-table';
    import CaptureGradeCell from '@/components/Capture/CaptureGradeCell.vue';
    import CaptureTableWarning from '@/components/Capture/CaptureTableWarning.vue';
    import CaptureTableHeader from '@/components/Capture/CaptureTableHeader.vue';

    interface IBulkEditStore {
        admingrade: string;
        grade: number;
    }

    const users = ref< ICaptureUser[] >([]);
    const userids = ref< number[] >([]);
    const itemid = ref(0);
    const categoryid = ref(0);
    const groupid = ref(0);
    const totalrows = ref(0);
    const currentpage = ref(1);
    const rowsperpage = ref(25);
    const datatablekey = ref(1);
    const filterkey = ref(0);
    const usershidden = ref(false);
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
    const editsaving = ref(false);
    const editblocking = ref(new Set<number>());
    const showconversion = ref(false);
    const provisionalslot = ref('');
    const provisionalid = ref('');
    const showcsvimport = ref(true);
    const debug = ref({});
    const staffuserid = ref(0);
    const caneditgrades = ref(false);
    const toast = useToast();
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );
    const {monochrome, updateLogo} = useLogo();
    const filterstore = useFilter();
    const { firstname, lastname } = storeToRefs( filterstore );
    const capturecellform = ref< ICaptureCellForm | null >(null);
    
    // store changed cells in bulk edit mode
    let bulkeditstore: IBulkEditStore[] = [];

    type GradeRow = Record<string, any>;
    const columnHelper = createColumnHelper<GradeRow>();

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
            console.error(error);
            debug.value = error;
        });
    });

    /**
     * computed for tablecolumns.
     * This is basically where the table is defined.
     * (TanStack table version)
     */
    const tablecolumns = computed(() => {
        const cols = [];

        if (!usershidden.value) {

            // First initial (column hidden)
            cols.push(columnHelper.accessor('firstinitial', {
                header: 'firstinitial'
            }));

            // Last initial (column hidden)
            cols.push(columnHelper.accessor('lastinitial', {
                header: 'lastinitial'
            }));

            // Note
            cols.push(columnHelper.display({
                id: 'note',
                header: mstringstore.getMstring('note'),
                cell: ({row}) => {
                    const user = row.original;
                    return h(NoteButton, {
                        gradeitemid: itemid.value,
                        userid: user.id,
                        name: user.displayname + ' for ' + itemname.value,
                        shortnote: user.shortnote,
                        onUpdated: () => get_user_data(user.id),
                    })
                }
            }));

            // Displayname (not hidden)
            cols.push(columnHelper.accessor('displayname', {
                header: mstringstore.getMstring('firstnamelastname')
            }));
        } else {

            // Displayname (hidden)
            cols.push(columnHelper.accessor('displayname', {
                header: mstringstore.getMstring('participant')
            }));           
        }

        // ID Number
        cols.push(columnHelper.accessor('idnumber', {
            header: mstringstore.getMstring('idnumber')
        }));    
        
        // Actions
        cols.push(columnHelper.display({
            id: 'actions',
            header: mstringstore.getMstring('actions'),
            cell: ({row}) => {
                if (ineditcellmode.value) {
                    return '';
                } else {
                    const user = row.original;
                    return h(CaptureMenu, {
                        item: user,
                        itemid: itemid.value,
                        categoryid: categoryid.value,
                        userid: user.id,
                        name: user.displayname,
                        itemname: itemname.value,
                        gradesimported: gradesimported.value,
                        awaitingcapture: user.awaitingcapture,
                        gradehidden: user.gradehidden,
                        converted: converted.value,
                        caneditgrades: caneditgrades.value,
                        onGradeadded: () => get_user_data(user.id),
                    })
                }
            }
        }));

        // Alerts.
        if (showalert.value) {
            cols.push(columnHelper.display({
                id: 'alert',
                header: mstringstore.getMstring('warnings'),
                cell: ({row}) => {
                    const user = row.original;
                    return h(CaptureTableWarning, {
                        user: user,
                    })
                }
            }))
        }

        // Loop over columns
        columns.value.forEach(column => {
            cols.push(columnHelper.accessor('GRADE' + column.id, {
                // header: column.description,
                header: () => {
                    return h(CaptureTableHeader, {
                        column: column,
                        caneditgrades: caneditgrades.value,
                        ineditcellmode: ineditcellmode.value,
                        itemid: itemid.value,
                        onColumnchanged: () => reload_page(),
                        onEditcolumn: () => bulk_edit_clicked(column),
                        onBulkcancel: () => bulkedit_cancel(),
                        onBulksave: () => bulkedit_save(column),
                    })
                },
                cell: ({row}) => {
                    const user = row.original;
                    return h(CaptureGradeCell, {
                        user: user,
                        column: column,
                        form: capturecellform.value,
                        editcolumnid: editcolumnid.value,
                        onUpdate: (grade) => bulk_edit_update(grade, user, column),
                    })
                }
            }));
        });

        return cols;
    });

    /**
     * Computed for table filters
     * (TanStack table version)
     */
    const tablefilters = computed(() => {
        const filters: ColumnFiltersState = [];

            if (firstname.value != 'all') {
            filters.push({
                id: 'firstinitial',
                value: firstname.value,
            });
        }

        if (lastname.value != 'all') {
            filters.push({
                id: 'lastinitial',
                value: lastname.value,
            });
        }

        return filters;
    })

    /**
     * Bulk edit cog clicked
     */
    function bulk_edit_clicked(column: any) {

        moodleFetch('local_gugrades_get_capture_cell_form',
            {
                gradeitemid: itemid.value,
            }
        )
        .then((result: any) => {
            capturecellform.value = result;
            editcolumnid.value = column?.id ?? null;
            bulkeditstore = [];

            // Add admingrade onto front of admin grades
            capturecellform.value!.adminmenu.unshift({
                value: 'GRADE',
                label: mstrings.value['admingrade']! + '...',
            });
        })
        .catch((error) => {
            console.error(error);
        });
    }

    /**
     * A cell has been updated by Bulk edit - store the update for later. 
     */
    function bulk_edit_update(bulkitem: any, user: any, column: any) {
        bulkeditstore[user.id] = bulkitem;

        console.log(user);
    }

    /**
     * Bulk edit has been cancelled
     * 
     */
    function bulkedit_cancel() {
        editcolumnid.value = 0;
        bulkeditstore = [];

        reload_page();
    }

    /**
     * Bulkedit has been saved
     */
    function bulkedit_save(column: ICaptureColumn) {
        console.log("BULK SAVE", column);

        // 1. Create an array to hold all active network requests
        const promises: Promise<any>[] = [];

        bulkeditstore.forEach((bulkitem, userid) => {
            const admingrade = bulkitem.admingrade;
            const grade = bulkitem.grade;
            const notes = '';

            // Capture the fetch promise
            const request = moodleFetch(
                'local_gugrades_write_additional_grade',
                {
                    gradeitemid: itemid.value,
                    userid: userid,
                    admingrade: admingrade === 'GRADE' ? '' : admingrade,
                    reason: column.gradetype,
                    other: column.other,
                    scale: column.points ? 0 : grade,
                    grade: column.points ? grade : 0,
                    notes: notes,
                }
            )
            .catch((error) => {
                console.error(`Failed to save for user ${userid}:`, error);
                debug.value = error;
            });

            promises.push(request);
        });

        // 2. Wait for all requests to finish using a standard .then() callback
        Promise.all(promises)
            .then(() => {
                console.log("All grades saved successfully!");
            })
            .catch((err) => {
                console.error("An error occurred during bulk save:", err);
            })
            .finally(() => {
                // 3. This executes strictly AFTER all network calls resolve or fail
                editcolumnid.value = 0;
                reload_page();
            });
    }


    /**
     * Number of pages changed
     */
    function pagination_change(rows: any) {
        rowsperpage.value = rows.length;
        currentpage.value = 1;
        datatablekey.value++;
        nextTick(() => {
            filterkey.value++;
        });
    }

    /**
     * Table name filter
     */
    const table_filter = computed(() => {
        const options = [];

        if (firstname.value != 'all') {
            options.push({
                field: 'firstinitial',
                comparison: '=',
                criteria: firstname.value,
            });
        }

        if (lastname.value != 'all') {
            options.push({
                field: 'lastinitial',
                comparison: '=',
                criteria: lastname.value,
            });
        }

        return options.length ? options : null;
    });

    /**
     * A watch for the itemid changing
     * Lots of stuff gets reset if the itemid is changed.
     */
    watch(itemid, () => {
        currentpage.value = 1;
        revealnames.value = false;
        editcolumn.value = '';
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
        editsaving.value = false;
        editblocking.value = new Set();
        reload_page();
    }

    /**
     * A bulk-edit cell reported whether it currently has an invalid value
     * that should block Save.
     */
    function edit_validity_change(payload: { userid: number, blocking: boolean }) {
        const next = new Set(editblocking.value);
        if (payload.blocking) {
            next.add(payload.userid);
        } else {
            next.delete(payload.userid);
        }
        editblocking.value = next;
    }

    /**
     * In edit mode, the save button is clicked.
     * Flag shouldsave before unmounting so cells persist; navigation alone must not.
     */
    async function edit_cell_saved() {
        if (editblocking.value.size > 0) {
            toast.error(mstrings.value['bulkgradeinvalid'] || 'One or more grades are invalid.');
            return;
        }
        editsaving.value = true;
        await nextTick();
        editcolumn.value = '';
        editblocking.value = new Set();
    }

    /**
     * In edit mode, the cancel button is clicked
     * Set editcancelled to true and pass as prop to edit cells
     * so it knows not to save.
     */
    function edit_cell_cancelled() {
        editsaving.value = false;
        editcancelled.value = true;
        editblocking.value = new Set();
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
            editsaving.value = false;

            reload_page();
        },
        { debounce: 500, maxWait: 1000 },
    );

    /**
     * Are we in "edit a cell" mode?
     * Stuff doesn't appear, if so, and 'Save' button appears.
     */
    const ineditcellmode = computed(() => {
        return editcolumnid.value != 0;
    });

    /**
     * Get headers for table
     * These also define what data is displayed.
     */
    const headers = computed(() => {
        let heads = [];
        if (!usershidden.value) {
            heads.push({text: 'firstinitial', value: 'firstinitial'}),
            heads.push({text: 'lastinitial', value: 'lastinitial'}),
            heads.push({text: mstrings.value['userpicture'], value: "slotuserpicture"});
            heads.push({text: mstrings.value['note'], value: "slotnote"});
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

                    // This re-references the array and is VITAL because tanstack is shit
                    // at spotting that data in the array has changed (only the array itself)
                    users.value[found] = result;
                    users.value = [...users.value];
                }
            }
        })
        .catch((error) => {
            window.console.error(error);
            debug.value = error;
        });
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
