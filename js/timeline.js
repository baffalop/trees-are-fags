function Timeline(settings)
{
    this.pad = settings.pad;
    this.offsetY = settings.offsetY;
    this.segInterval = settings.segInterval;
    this.lastValue = 0;
    this.dim = settings.dimensions;

    this.start = [this.pad, this.dim[1] - this.pad];
    this.end = [this.dim[0] - this.pad, this.start[1] - this.offsetY];
    this.range = this.end[1] - this.pad;

    // setup first line segment
    this.segs = [];
    let firstPoint = this.start;
    firstPoint.push(0); // 2th index is time value
    const secondPoint = this.pickPoint(this.segInterval);
    const firstSeg = [firstPoint, secondPoint];
    this.segs.push(firstSeg);

    // and finally, our canvas
    this.canvas = $('#timeline')[0].getContext('2d');
    this.canvas.lineCap = 'round';
    this.canvas.lineWidth = settings.lineWeight;
    this.canvas.strokeStyle = 'rgba(255,255,255,0.9)';
}

Timeline.prototype =
{
    interp: function(pointFrom, pointTo, value) {
        const vector = [pointTo[0] - pointFrom[0], pointTo[1] - pointFrom[1]];
        const x = pointFrom[0] + value * vector[0];
        const y = pointFrom[1] + value * vector[1];
        return [x, y, value];
    },

    // interpolate for whole timeline
    valueToPoint: function(value) {
        return this.interp(this.start, this.end, value);
    },

    // pick a new point at [value] along the line, with Y wiggle
    pickPoint: function(value) {
        let point = this.valueToPoint(value); // choose a point along the baseline
        point[1] -= Math.random()*this.range; // add a random Y offset
        return point;
    },

    // commence a new segment of the line
    newSeg: function() {
        const lastSeg = this.segs.length - 1;
        const joint = this.segs[lastSeg][1]; // make end of last seg start of new one
        const newValue = this.segs[lastSeg][1][2] + this.segInterval;
        const segEnd = this.pickPoint(newValue);
        this.segs.push([joint, segEnd]);
    },

    // commence a new segment, broken off from the previous one
    newBranch: function() {
        this.newSeg();
        const lastSeg = this.segs.length-1;
        this.segs[lastSeg][0] = this.pickPoint(this.segs[lastSeg][0][2]);
    },

    draw: function(value) {
        let lastSeg = this.segs.length - 1;
        // add new segments to reach value
        while (value > this.segs[lastSeg][1][2]) {
            this.newSeg();
            lastSeg ++;
        }
        // get first segment that needs redrawn
        let i = lastSeg - 1;
        for (; i >= 0 && this.lastValue < this.segs[i][1][2]; i--);
        i = i < 0 ? 0 : i;
        // clear canvas from segs[lastSeg][0][0] to segs[lastSeg][1][0]
        this.canvas.clearRect(this.segs[i][0][0], 0, this.dim[0], this.dim[1]);
        // fill in all previously unrendered whole segments
        for (; i < lastSeg && value < this.segs[i][1][2]; i ++) {
            this.canvas.moveTo(this.segs[i][0][0], this.segs[i][0][1]);
            this.canvas.lineTo(this.segs[i][1][0], this.segs[i][1][1]);
            this.canvas.stroke();
        }
        // remove spare segments
        while (i < this.segs.length - 1) this.segs.pop();
        // draw current segment
        const relativeValue = (value-this.segs[i][0][2]) / (this.segs[i][1][2] - this.segs[i][0][2]);
        const endPoint = this.interp(this.segs[i][0], this.segs[i][1], relativeValue);
        this.canvas.moveTo(this.segs[i][0][0], this.segs[i][0][1]);
        this.canvas.lineTo(endPoint[0], endPoint[1]);
        this.canvas.stroke();
        this.lastValue = value;
    }
};
