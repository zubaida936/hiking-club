/**
 * Event create/edit form: live difficulty preview and the meeting points
 * repeater. Formula parameters come from the server (DifficultyCalculator::toArray),
 * so this preview always matches what is saved.
 */
const LEVEL_CLASSES = {
    1: 'bg-emerald-100 text-emerald-800',
    2: 'bg-sky-100 text-sky-800',
    3: 'bg-amber-100 text-amber-800',
    4: 'bg-red-100 text-red-800',
};

let nextKey = 0;

export default ({ calculator, distance, gain, loss, meetingPoints }) => ({
    distance: Number(distance) || 0,
    gain: Number(gain) || 0,
    loss: Number(loss) || 0,
    meetingPoints: meetingPoints.map((p) => ({ ...p, _key: nextKey++ })),

    get score() {
        const km = Math.max(0, this.distance || 0)
            + Math.max(0, this.gain || 0) / calculator.gainPerKm
            + Math.max(0, this.loss || 0) / calculator.lossPerKm;

        return Math.round(calculator.multiplier * km);
    },

    get level() {
        return calculator.levels.find((l) => l.max === null || this.score <= l.max);
    },

    get levelClasses() {
        return LEVEL_CLASSES[this.level.value];
    },

    addPoint() {
        this.meetingPoints.push({ name: '', pickup_time: '', _key: nextKey++ });
    },

    move(index, offset) {
        const target = index + offset;
        if (target < 0 || target >= this.meetingPoints.length) return;
        const [item] = this.meetingPoints.splice(index, 1);
        this.meetingPoints.splice(target, 0, item);
    },
});
