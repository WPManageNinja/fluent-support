<template>
    <Transition name="fs-modal">
        <div v-if="show" class="fs-modal-mask">
            <div class="fs-modal-wrapper">
                <div class="fs-modal-container">
                    <div class="fs-modal-header">
                        <slot name="header">
                            <h3 class="fs-modal-title">{{ title }}</h3>
                            <el-button style="color:red; border: none; font-size: 20px;" icon="Close" @click="$emit('close')"/>
                        </slot>
                    </div>

                    <div class="fs-modal-body">
                        <slot name="body"></slot>
                    </div>

                    <div class="fs-modal-footer">
                        <slot name="footer"></slot>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script>
export default {
    name: "Modal",
    props: {
        show: {
            type: Boolean,

        },
        title: {
            type: String,
            default() {
                return '';
            }
        },
    }
}
</script>

<style lang="scss">
.fs-modal-mask {
    position: fixed;
    z-index: 12;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    transition: opacity 0.3s ease;
    flex-wrap: nowrap;
    justify-content: center;
}

.fs-modal-container {
    width: 760px;
    max-height: calc(100% - 10%);
    overflow: auto;
    margin: 5% auto;
    padding: 15px 40px;
    background-color: #fff;
    border-radius: 2px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.33);
    transition: all 0.3s ease;
}

.fs-modal-wrapper {
     display: table-cell;
     vertical-align: middle;
}

.fs-modal-header{
    display: flex;
    justify-content: space-between;
    align-items: baseline;
}

.fs-modal-header h3 {
    color: #42b983;
}

.fs-modal-body {
    .el-form-item__content{
        display: flex;
        flex-direction: column;
        align-items: stretch;
    }
    margin: 20px 0;
}

.fs-modal-default-button {
    float: right;
}


/*
 * The following styles are auto-applied to elements with
 * transition="modal" when their visibility is toggled
 * by Vue.js.
 *
 * You can easily play with the modal transition by editing
 * these styles.
 */

.fs-modal-enter-from {
    opacity: 0;
}

.fs-modal-leave-to {
    opacity: 0;
}

.fs-modal-enter-from .fs-modal-container,
.fs-modal-leave-to  .fs-modal-container {
    -webkit-transform: scale(1.1);
    transform: scale(1.1);
}

/* Media queries
 *
 * For mobile devices, we adjust the modal's position and size.
 *
 * As we won't be using this modal everywhere in project so we did the media queries here.
 */
@media (max-width: 768px) {
    .fs-modal-container {
        min-width: unset;
        height: calc(100% - 30%);
        margin: 14% auto;
        width: 360px;
    }
}
</style>
