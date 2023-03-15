export default function Select({onChange}){
    return(
        <div class="textright">
            <select onChange={onChange}>
                <option value="last_7_days">Last 7 Days</option>
                <option value="last_15_days">Last 15 Days</option>
                <option value="last_1_month">Last Month</option>
            </select>
        </div>
    )
}