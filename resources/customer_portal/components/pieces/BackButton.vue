<template>
    <div class="fs_back_nav">
        <el-button link class="fs_back_button" @click="$router.push({ name: 'dashboard' })">
            <svg class="fs_svg_back" width="6" height="10" viewBox="0 0 6 10" fill="none"
                 xmlns="http://www.w3.org/2000/svg">
                <path
                    d="M2.2045 4.99907L5.917 8.71157L4.8565 9.77207L0.0834961 4.99907L4.8565 0.226074L5.917 1.28657L2.2045 4.99907Z"
                    fill="currentColor"/>
            </svg>
            <span class="fs_back_btn">{{ $t('Back to All Tickets') }}</span>
        </el-button>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'BackButton',
}
</script>
