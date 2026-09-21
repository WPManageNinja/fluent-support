<!-- 
 Composition API unchanged

 reason: Chart.js integration and lifecycle management. 
 In options API, managing chart instance and cleanup is more cumbersome.

-->
<template>
    <canvas ref="chartCanvas"></canvas>
</template>

<script type="text/babel">
import { defineComponent, ref, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'
import Chart from 'chart.js/auto'

export default defineComponent({
    name: 'MonthlyChart',
    props: {
        chartData: {
            type: Object,
            required: true
        },
        chartOptions: {
            type: Object,
            required: false,
            default: () => ({})
        },
    },
    setup(props) {
        const chartCanvas = ref(null)
        let chartInstance = null
        let isDestroyed = false

        const createChart = () => {
            if (isDestroyed || !chartCanvas.value) return
            
            const ctx = chartCanvas.value.getContext('2d')
            if (!ctx) return

            // Destroy existing chart if any
            if (chartInstance) {
                try {
                    chartInstance.destroy()
                } catch (e) {
                    // Ignore destruction errors
                }
                chartInstance = null
            }

            try {
                chartInstance = new Chart(ctx, {
                    type: 'bar',
                    data: props.chartData,
                    options: {
                        ...props.chartOptions,
                        animation: {
                            duration: 300
                        },
                        responsive: true,
                        maintainAspectRatio: false
                    }
                })
            } catch (error) {
                console.error('Chart creation error:', error)
            }
        }

        const updateChart = () => {
            if (isDestroyed || !chartInstance) return
            
            try {
                chartInstance.data = props.chartData
                chartInstance.update('none')
            } catch (error) {
                // Chart may have been destroyed, try recreating
                createChart()
            }
        }

        watch(() => props.chartData, () => {
            if (!isDestroyed) {
                updateChart()
            }
        }, { deep: true })

        onMounted(() => {
            nextTick(() => {
                createChart()
            })
        })

        onBeforeUnmount(() => {
            isDestroyed = true
            if (chartInstance) {
                try {
                    // Stop animations first
                    chartInstance.stop()
                    // Clear the canvas
                    chartInstance.clear()
                    // Destroy the chart
                    chartInstance.destroy()
                } catch (error) {
                    // Silently ignore cleanup errors
                }
                chartInstance = null
            }
        })

        return {
            chartCanvas
        }
    }
})
</script>
