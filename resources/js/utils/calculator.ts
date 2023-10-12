import { toRefs, reactive, computed } from 'vue';
const b = '789456123.0=,DEL,/,x,-,+,CL';
const operators  = b.slice(13).split(',')

export const useCalculatorInput = () => {
    const state = reactive({
      current: 0,
      history: [],
      mode: {
        initial: true,
        deleted: false,
        result: false,
        error: false
      },
      last: '',
      time: '',
    });

    const currentValue = computed(() => {
        return  state.current
    });

    // Calculate related Functions
    function calculate(expression: string) {
      let result = orderExpression(expression)
      let lastLength = result.length
      state.history = [...result];

      while (result.length > 1) {
        for (let i = 0 ; i < result.length; i++) {
          const operator = result[i]
          const left = result[i - 1]
          const right = result[ i + 1]
          const haveHighOrder =  result.includes('x') || result.includes('/');
          const highOrder =  'x/'.includes(operator)
          const lowOrder =  '+-'.includes(operator)

          if (haveHighOrder && highOrder) {
            const value = doOperation(left, operator, right)
            result.splice(i - 1, 3, value)
            break;
          } else if (!haveHighOrder && lowOrder){
            const value = doOperation(left, operator, right)
            result.splice(i - 1, 3, value)
            break;
          }
        }

        if (lastLength == result.length) {
          state.mode.error = true
          break
        }
      }
      return state.mode.error ? null : result[0]
    }

    function orderExpression(expression = '') {
      let localHistory: string|number[] = state.history
      const operations: string[] = []
      localHistory = expression.split('').map((n) => {
        if (operators.includes(n)){
          operations.push(n)
          return '|'
        }
        return n
      })

      const numbers: number[] = localHistory.join('').split('|').map(num => parseFloat(num || "0"))
      localHistory = [];

      numbers.forEach((digit: number, i: number) => {
        localHistory.push(digit)
        if (operations[i])
          localHistory.push(operations[i])
      })

      return localHistory
    }

    function doOperation(left: string, operator:string, right: string) {
        const firstNumber = parseFloat(left ?? 0);
        switch(operator){
         case '/':
            const result = firstNumber / parseFloat(right)
            return result.toFixed(2)
         case 'x':
            return firstNumber * parseFloat(right)
          case '-':
            return firstNumber - parseFloat(right)
          case '+':
           return firstNumber  + parseFloat(right)
           default:
            return 0;
        }
    }

    return {
       ...toRefs(state),
       currentValue,
       calculate
    }
}
