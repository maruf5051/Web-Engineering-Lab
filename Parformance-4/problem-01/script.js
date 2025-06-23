
let students = [
    {
        name: "Sadikul", 
        roll: 5056,
        Bangla: 80,
        English: 85,
        CSE115: 79,
        MAT101:70,
        attendance: true,
    },

    {
        name: "Maruf", 
        roll: 5051,
        Bangla: 85,
        English: 85,
        CSE115: 70,
        MAT101:75,
        attendance: true,
    },

    {
        name: "Saaim", 
        roll: 4844,
        Bangla: 80,
        English: 85,
        CSE115: 79,
        MAT101:70,
        attendance: false,
    },

    {
        name: "Habib", 
        roll: 5174,
        Bangla: 80,
        English: 85,
        CSE115: 79,
        MAT101:70,
        attendance: true,
    }
]

students.forEach( students=>
    {
        let totalScore = 0;

        if (students.attendance === false)
        {
            console.log("Not Eligible")
        }

        else{
            totalScore = students.Bangla + students.English + students.CSE115 + students.MAT101;
            console.log(`${students.name} is eligible and total score is ${totalScore}`);
        }
    }

);

