import { Ref, toRefs, reactive, onMounted, computed } from 'vue';
const b = '789456123.0=,DEL,/,x,-,+,CL';
const buttons    = b.slice(0,12).split('')
const operators  = b.slice(13).split(',')
const changers = ['=','DEL','CL']

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
    })

    function resolveAction(val) {
      (val == '=') ? state.calculate() : this[val]()
    }

    function CL() {
      state.current = 0
      state.history = []
      state.mode.initial = true
    }

    function DEL() {
      const len = state.history.length
      state.history.pop()
      if (state.history.length) {
       state.current = 0
       state.last = state.current[0]
       state.setMode('deleted', true)
      } else {
       state.CL()
      }
    }

    // Calculate related Functions
    function calculate(expression) {
      let result = orderExpression(expression)
      let lastLength = result.length

      while (result.length > 1) {
        for (let i = 0 ; i < result.length; i++) {
          const operator = result[i]
          const left = result[i - 1]
          const right = result[ i + 1]
          const haveHighOrder =  result.includes('x') || result.includes('/');
          const highOrder =  'x/'.includes(operator)
          const lowOrder =  '+-'.includes(operator)

          if (highOrder) {
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
      let history = state.history
      const operations = []
      history = expression.split('').map((n) => {
        if (operators.includes(n)){
          operations.push(n)
          return '|'
        }
        return n
      })

      const numbers = history.join('').split('|')
      history = [];

      numbers.forEach((n, i) => {
        history.push(n)
        if (operations[i])
          history.push(operations[i])
      })

      return history
    }

    function doOperation(left, operator, right) {
       switch(operator){
         case '/':
            const result = parseFloat(left) / parseFloat(right)
            return result.toFixed(2)
            break;
         case 'x':
            return parseFloat(left) * parseFloat(right)
            break;
          case '-':
            return parseFloat(left) - parseFloat(right)
            break;
          case '+':
           return parseFloat(left)  + parseFloat(right)
        }
    }

   //  decoration styles and behavior ralated

    function setMode(name, value){
      state.mode[name] = value
    }

    return {
       ...toRefs(state),
       currentValue,
       calculate
    }
}
