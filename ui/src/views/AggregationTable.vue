<template>
    <DebugDisplay :debug="serverdebug"></DebugDisplay>

    <div class="bg-brand-light-purple/10 border rounded-md mt-2 border-gray-300 shadow-sm">

        <div class="p-2">
            <div class="flex gap-2 justify-start mb-4">
                <LevelOneSelect  @levelchange="levelOneChange"></LevelOneSelect>
                <GroupSelect v-if="level1category" @groupselected="groupselected"></GroupSelect>
            </div>

            <!-- Buttons line -->
            <AggregationButtons
                v-if="level1category && aggregationsupported"
                :categoryid="categoryid"
                :gradeitemid="gradeitemid"
                :groupid="groupid"
                :toplevel="toplevel"
                :atype="atype"
                :allowconversion="allowconversion"
                :allowrelease="allowrelease"
                :released="released"
                :staffuserid="staffuserid"
                :caneditgrades="caneditgrades"
                @refreshtable="table_update"
                ></AggregationButtons>
        </div>

        <AlertsBlock :errors="errors" />
    </div>

    <!-- Aggregation is not possible -->
     <UAlert v-if="!aggregationsupported" variant="error" class="my-5">{{  mstrings.aggregationnotsupported }}</UAlert>

    <div v-if="level1category && aggregationsupported" class="mt-2">

        <!-- Breadcrumb trail -->
        <div v-if="breadcrumb.length > 1" class="my-3 overflow-visible">
            <div class="text-sm p-2 rounded-lg border border-university-blue/40 bg-university-blue/10 text-university-blue shadow-sm">
                <ul class="flex flex-wrap items-center gap-2">
                    
                    <li class="flex items-center text-university-blue/70">
                        <FolderOpen :size="18" />
                    </li>

                    <li 
                        v-for="(item, index) in breadcrumb" 
                        :key="item.id"
                        class="flex items-center gap-2"
                    >
                        <span v-if="index > 0" class="text-university-blue/40 select-none">/</span>

                        <UTooltip :text="item.fullname">
                            <a
                                href="#"
                                @click.prevent="expand_clicked(item.id)"
                                class="hover:underline transition-colors block py-0.5"
                                :class="{ 
                                    'font-bold text-university-blue': index === breadcrumb.length - 1,
                                    'text-university-blue/80 hover:text-university-blue': index !== breadcrumb.length - 1
                                }"
                            >
                                {{ item.shortname }}
                            </a>
                        </UTooltip>

                    </li>
                </ul>
            </div>
        </div>

        <!-- Please wait spinner -->
        <PleaseWait v-if="loading"></PleaseWait>

        <!-- NEW TANSTACK TABLE -->
        <UTable 
            v-if="!loading"
            :data="users"
            :columns="tablecolumns" 
            :filters="tablefilters"
            :visibility="{firstinitial: false, lastinitial: false}"
            :initial-sort="[{ id: 'displayname', desc: false }]" 
            class="my-8"
        />
    </div>
</template>

<script setup lang="ts">
    import {ref, computed, onMounted, h } from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import LevelOneSelect from '@/components/Common/LevelOneSelect.vue';
    import GroupSelect from '@/components/Common/GroupSelect.vue';
    import PleaseWait from '@/components/Common/PleaseWait.vue';
    import AggregationButtons from '@/components/Aggregation/AggregationButtons.vue';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import { ArrowBigRight, ArrowBigLeft, FolderOpen } from '@lucide/vue';
    import type { IBreadcrumb, IColumn, IUser, IUserField, IWarning, IError } from '@/js/Interfaces';
    import UAlert from '@/components/Common/UAlert.vue';
    import UTooltip from '@/components/Common/UTooltip.vue';
    import AggregationTableHeader from '@/components/Aggregation/AggregationTableHeader.vue';
    import AggregationGradeCell from '@/components/Aggregation/AggregationGradeCell.vue';
    import AlertsBlock from '@/components/Common/AlertsBlock.vue';
    import { useFilter } from '@/stores/filter';
    import UTable from '@/components/Common/UTable.vue';
    import { createColumnHelper, type ColumnFiltersState } from '@tanstack/vue-table';
    import ResitRequired from '@/components/Aggregation/ResitRequired.vue';
    import CompletionPercentage from '@/components/Aggregation/CompletionPercentage.vue';
    import TotalCell from '@/components/Aggregation/TotalCell.vue';
    import TotalHeader from '@/components/Aggregation/TotalHeader.vue';
    import BackButton from '@/components/Aggregation/BackButton.vue';
    import ReleaseGrade from '@/components/Aggregation/ReleaseGrade.vue';

    interface IAggregationHeader {
        infocol?: boolean;
        text: string;
        value: string;
        sortType?: string;
        sortable?: boolean;
        excludeempty?: boolean;
        strategy?: string;
        categoryid?: number;
        fullname?: string;
        gradeitemid?: number;
        grademax?: number;
        gradetype?: string;
        isscale?: boolean;
        released?: boolean;
        resititem?: boolean;
        showweights?: boolean;
        slot?: string;
        weight?: number;
        atype?: string;
    }

    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );
    const level1category = ref(0);
    const loading = ref(true);
    const currentpage = ref(1);
    const rowsperpage = ref(25);
    const datatablekey = ref(1);
    const aggregationsupported = ref(true);
    const categoryid = ref(0);
    const gradeitemid = ref(0);
    const groupid = ref(0);
    const items = ref([]);
    const users = ref< IUser[] >([]);
    const columns = ref< IColumn[] >([]);
    const categories = ref([]);
    const breadcrumb = ref< IBreadcrumb[] >([]);
    const backid = ref(0);
    const toplevel = ref(false);
    const completed = ref(0);
    const atype = ref('');
    const formattedatype = ref('');
    const warnings = ref< IWarning[] >([]);
    const strategy = ref('');
    const debug = ref([]);
    const conversion = ref('');
    const allowconversion = ref(false);
    const serverdebug = ref({});
    const allowrelease = ref(false);
    const released = ref(false);
    const showweights = ref(false);
    const excludeempty = ref(false);
    const staffuserid = ref(0);
    const caneditgrades = ref(false);
    const completionused = ref(false);
    const filterstore = useFilter();
    const issetupcomplete = ref(false);
    const { firstname, lastname } = storeToRefs( filterstore );

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

        // First initial (column hidden)
        cols.push(columnHelper.accessor('firstinitial', {
            header: 'firstinitial'
        }));

        // Last initial (column hidden)
        cols.push(columnHelper.accessor('lastinitial', {
            header: 'lastinitial'
        }));

        // Displayname (not hidden)
        cols.push(columnHelper.accessor('displayname', {
            header: mstringstore.getMstring('firstnamelastname')
        }));

        // ID Number
        cols.push(columnHelper.accessor('idnumber', {
            header: mstringstore.getMstring('idnumber')
        })); 

        // Back button goes here.
        if (!toplevel.value) {
            cols.push(columnHelper.display({
                id: 'back',
                header: () => {
                    return h(BackButton, {
                        onBackclick: () => expand_clicked(backid.value)
                    });
                },
                cell: '',
            }))
        }

        // Iterate over grade item columns.
        columns.value.forEach(column => {
            cols.push(columnHelper.accessor(column.fieldname, {
                header: (context) => {
                    return h(AggregationTableHeader, {
                        column: column,
                        headercontext: context,
                        onExpandclicked: () => expand_clicked(column.categoryid),
                    });
                },
                cell: ({row, table}) => {
                    const user = row.original;
                    const rows = table.getRowModel().rows;
                    const indexOnPage = rows.findIndex(r => r.id === row.id);
                    const isBeforeHalfway = indexOnPage < rows.length / 2;
                    return h(AggregationGradeCell, {
                        user: user,
                        column: column,
                        level1category: level1category.value,
                        caneditgrades: caneditgrades.value,
                        beforehalfway: isBeforeHalfway,
                        onGradeadded: (userid) => grade_changed(userid),
                    });
                }
            }));
        });

        if (toplevel.value) {

            // Resit required
            cols.push(columnHelper.accessor('resitrequired', {
                header: mstringstore.getMstring('resitrequired'),
                cell: ({row}) => {
                    const user = row.original;
                    return h(ResitRequired, {
                        user: user,
                        caneditgrades: caneditgrades.value,
                        onUserupdated: () => user_update(user.id),
                    });
                }
            }));

            // Completion.
            if (completionused.value) {
                cols.push(columnHelper.accessor('completed', {
                    header: mstringstore.getMstring('completed'),
                    cell: ({row}) => {
                        const user = row.original;
                        return h(CompletionPercentage, {
                            user: user
                        });
                    }
                }));
            }

            // "Grand" Total
            cols.push(columnHelper.accessor('coursetotal', {
                header: (context) => {
                    return h(TotalHeader, {
                        shortname: mstringstore.getMstring('coursetotal'),
                        strategy: strategy.value,
                        headercontext: context,
                    });
                },
                cell: ({row, table}) => {
                    const user = row.original;
                    const rows = table.getRowModel().rows;
                    const indexOnPage = rows.findIndex(r => r.id === row.id);
                    const isBeforeHalfway = indexOnPage < rows.length / 2;
                    return h(TotalCell, {
                        user: user,
                        toplevel: toplevel.value,
                        gradeitemid: gradeitemid.value,
                        level1category: level1category.value,
                        categoryid: categoryid.value,
                        showweights: showweights.value,
                        caneditgrades: caneditgrades.value,
                        beforehalfway: isBeforeHalfway,
                        onGradeadded: (userid) => grade_changed(userid),
                    });
                }
            }));
        } else {

            // Subcategory total
            cols.push(columnHelper.accessor('total', {
                header: (context) => {
                    return h(TotalHeader, {
                        shortname: mstringstore.getMstring('subcattotal'),
                        strategy: strategy.value,
                        headercontext: context,
                    });
                },
                cell: ({row, table}) => {
                    const user = row.original;
                    const rows = table.getRowModel().rows;
                    const indexOnPage = rows.findIndex(r => r.id === row.id);
                    const isBeforeHalfway = indexOnPage < rows.length / 2;
                    return h(TotalCell, {
                        user: user,
                        toplevel: toplevel.value,
                        gradeitemid: gradeitemid.value,
                        level1category: level1category.value,
                        categoryid: categoryid.value,
                        showweights: showweights.value,
                        caneditgrades: caneditgrades.value,
                        beforehalfway: isBeforeHalfway,
                        onGradeadded: (userid) => grade_changed(userid),
                    });
                }
            }));
        }

        // Released grade
        if (released.value && !toplevel.value) {
            cols.push(columnHelper.accessor('releasegrade', {
                header: mstringstore.getMstring('released'),
                cell: ({row}) => {
                    const user = row.original;
                    return h(ReleaseGrade, {
                        grade: user.releasegrade
                    });
                }
            }));
        }

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
    });

    /**
     * Capture change to top level category dropdown
     * @param {*} level
     */
    function levelOneChange(level: number) {

        level1category.value = level;
        categoryid.value = level1category.value;
        if (categoryid.value) {

            // Don't fire update during initial setup
            table_update();
        }
    }

    /**
     * Capture change to group
     */
     function groupselected(gid: number) {
        const newgid = Number(gid);
        if (newgid === groupid.value) return;
        groupid.value = newgid;

        // Don't fire during initial setup.
        table_update();
    }

    /**
     * Grade has been modified for user
     */
    function grade_changed(userid: number) {
        user_update(userid);
    }

    /**
     * Add columns to user array
     */
    function process_users(users: IUser[]) {
        users.forEach(user => {
            user.fields.forEach(field => {
                user[field.fieldname] = {
                    userid: user.id,
                    data: field.display,
                    dropped: field.dropped,
                    isadmin: field.isadmin,
                    hidden: field.hidden,
                    overridden: field.overridden,
                    available: field.available,
                };
            })
        });

        return users;
    }

    /**
     * Process columns for single user
     */
    function process_user(user: IUser) {
        user.fields.forEach(field => {
                user[field.fieldname] = {
                    userid: user.id,
                    data: field.display,
                    dropped: field.dropped,
                    isadmin: field.isadmin,
                    hidden: field.hidden,
                    overridden: field.overridden,
                    available: field.available,
                };
        });

        return user;
    }

    /**
     * Show the correct string for the aggregation type (atype)
     */
    function get_formattedatype() {
        if (atype.value == 'A') {
            return 'GGS1';
        } else if (atype.value == 'B') {
            return 'GGS2';
        } else if (atype.value == 'P') {
            return mstringstore.getMstring('points') + ' 100';
        } else if (atype.value == 'C') {
            return mstringstore.getMstring('converted');
        } else if (atype.value == 'E') {
            return 'Error';
        } else {
            return '[[' + atype.value + ']]';
        }
    };

    /**
     * mstring helper
     */
    const ms = (key: string): string => mstringstore.getMstring(key) ?? '';

    /**
     * Update single user (when something changes)
     */
    function user_update(userid: number) {

        moodleFetch(
            'local_gugrades_get_aggregation_user',
            {
                gradecategoryid: categoryid.value,
                userid: userid,
            }
        )
        .then((result: any) => {
            const found = users.value.findIndex((user) => {
                return user.id == userid;
            });
            if (found > -1) {
                users.value[found] = process_user(result);
            }

            // Bodge to force Tanstack to see change :(
            users.value = [...users.value];
        })
        .catch((error) => {
            window.console.error(error);
            serverdebug.value = error;
        });
    }

    /**
     * convert warnings to errors
     */
    const errors = computed< IError[] >(() => {
        return warnings.value.map(warning => {
            return {
                warning: warning.message,
                help: '',
                level: 'warning'
            }
        });
    });

    /**
     * Update table (when something changes)
     */
    function table_update() {

        // This is to prevent other things firing update during page load.
        //nextTick();
        issetupcomplete.value = true;

        // If we happen to end up here with no categoryid then just bail out.
        if (!Number.isInteger(categoryid.value)) {
            return;
        }

        loading.value = true;

        moodleFetch(
            'local_gugrades_get_aggregation_page',
            {
                gradecategoryid: categoryid.value,
                firstname: '',
                lastname: '',
                groupid: groupid.value,
                aggregate: false,
            }
        )
        .then((result: any) => {
            aggregationsupported.value = result.aggregationsupported;
            users.value = result.users;
            warnings.value = result.warnings;
            columns.value = result.columns;
            breadcrumb.value = result.breadcrumb;
            toplevel.value = result.toplevel;
            atype.value = result.atype;
            gradeitemid.value = result.gradeitemid;
            strategy.value = result.strategy;
            debug.value = result.debug;
            conversion.value = result.conversion;
            allowconversion.value = result.allowconversion;
            allowrelease.value = result.allowrelease;
            released.value = result.released;
            showweights.value = result.showweights;
            excludeempty.value = result.excludeempty;
            staffuserid.value = result.staffuserid;
            completionused.value = result.completionused;

            if (aggregationsupported.value) {

                // Get id of one back from breadcrumb
                if (breadcrumb.value.length >= 2) {
                    const crumb = breadcrumb.value.slice(-2);
                    if (crumb.length > 0) {
                        backid.value = crumb[0]!.id;
                    }
                }

                users.value = process_users(users.value);
                formattedatype.value = get_formattedatype();
            }

            loading.value = false;
        })
        .catch((error) => {
            window.console.error(error);
            serverdebug.value = error;
        });
    }

    /**
     * Expand button was clicked in header
     */
    function expand_clicked(id: number) {
        categoryid.value = id;
        table_update();
    }
</script>
