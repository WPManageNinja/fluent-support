<template>
    <el-card class="box-card fs_auth_box">
        <img :src="this.appVars.asset_url + '/images/help-scout-logo.svg'" alt="HelpScout" class="fs_help_scout_logo">
        <div class="text item">
           {{$t('Your Help Scout authorization code is:')}}  <code><strong>{{ code }}</strong></code>{{$t('. Please copy this code and paste it in the ')}}<code>{{$t('Authorization Code')}}</code> {{$t('field.')}}
        </div>
    </el-card>
</template>

<script>
export default {
    name: 'HelpScoutAuthorization',
    data() {
        return {
            code: '',
            error: '',
            loading: false,
        }
    },

    watch: {
        '$route.query': {
            handler: function (query) {
                if (query.code) {
                    this.code = query.code;
                }
            },
            immediate: true
        }
    }
}
</script>

<style lang="scss" scoped>
.fs_auth_box{
    width: 50%;
    margin: 0 auto;
    & .text.item {
        padding: 20px 0px;
        line-height: 2.3;
        text-align: center;
    }
    & img.fs_help_scout_logo {
        width: 30%;
        margin: 10px auto;
        display: block;
    }
}
</style>
