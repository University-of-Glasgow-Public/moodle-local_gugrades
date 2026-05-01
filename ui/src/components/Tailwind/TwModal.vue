<template>
    <Teleport to="#extension-mmplj">
        <dialog ref="modalRef" class="modal"  :class="{'modal-open': model}" style="z-index: 9999 !important;">
            <div class="modal-box" >
                <h3 class="text-lg font-bold">{{ title }}</h3>
                <div class="py-4">
                    <slot></slot>
                </div>
                <div class="modal-action">
                    <form method="dialog">
                        <!-- if there is a button in form, it will close the modal -->
                        <button class="btn">Close</button>
                    </form>
                </div>
            </div>
        </dialog>
    </Teleport>
</template>

<script setup>
    import { watch, ref, onMounted } from 'vue';
    import { initModals } from 'flowbite';

    const modalRef = ref(null);

    const model = defineModel();

    const props = defineProps({
        title: String,
    });

    onMounted(() => {
        initModals();
    });

    /**
    const openModal = () => {
        if (modalRef.value) {
            console.log('OPEN MODAL');
            //modalRef.value.showModal();
            modalRef.value.classList.add('modal-open');
        }
    };

    const closeModal = () => {
        if (modalRef.value) {
            modalRef.value.close();
        }
    };

    watch(model, (newval, oldval) => {
        if (newval) {
            openModal();
        } else {
            closeModal();
        }
        console.log(newval);
    });
    */
</script>

<style>
    .modal-open .tw\:modal-box {
        visibility: visible !important;
        opacity: 1 !important;
    }
</style>
